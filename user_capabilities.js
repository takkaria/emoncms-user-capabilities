'use strict'

const NotLoaded = -1

class CapabilityEditor {
    constructor({ apiRoot, rootUserId }) {
        this.apiRoot = apiRoot
        this.rootUserId = rootUserId

        this.roles = []
        this.currentRole = NotLoaded
        this.selectedUsers = []

        this.buttons = {
            removeUsersFromRole: document.getElementById('user-role-remove'),
            addUserToRole: document.getElementById('add-user-to-role'),
        }

        this.apiCall({
            endpoint:   'user_capabilities/list_roles',
            method:     'GET',
            data:       null,
            callback:   rawRoles => this.initialise(rawRoles)
        })
    }

    initialise(rawRoles) {
        // Turn the JSON data into an array for easy manipulation
        this.roles = []
        for (let key of Object.keys(rawRoles)) {
            this.roles.push(rawRoles[key])
        }

        // Init the current role
        this.buildRoleList()
        this.updateActiveRole(this.roles[0])
        this.initRoleAddButton()
        this.initCapabilityUpdateButton()
        this.initUserRemoveButton()
        this.initUserAddButton()
    }

    //
    // API calls
    //

    apiCall({ endpoint, method, data, callback }) {
        return $.ajax({
            url: this.apiRoot + endpoint,
            data: data,
            method: method,
            dataType: 'json',
            error: function(request, status, error) {
                const msg = request.responseJSON ?
                                request.responseJSON.message :
                                '[no message provided]'

                window.alert('Error: ' + msg)
            },
            success: function(data) {
                if (data.success === false) {
                    alert('Error: ' + data.message)
                } else {
                    callback(data)
                }
            },
        })
    }

    apiCreateRole(name) {
        this.apiCall({
            endpoint:   'user_capabilities/new_role',
            method:     'POST',
            data:       { name: name },
            callback:   data => {
                            this.roles.push(data)
                            this.buildRoleList()
                        }
        })
    }

    apiUpdateRole(id, elements) {
        this.apiCall({
            endpoint:   'user_capabilities/update_role_capabilities',
            method:     'POST',
            data:       {
                            id: id,
                            capabilities: JSON.stringify(elements),
                        },
            callback:   data => {
                            this.findRole(id).capabilities = data
                        }
        })
    }

    apiRemoveUsersFromRole(role, users) {
        this.apiCall({
            endpoint:   'user_capabilities/remove_users_from_role',
            method:     'POST',
            data:       {
                            roleid: role.id,
                            users: JSON.stringify(users)
                        },
            callback:   data => {
                            role.users = data
                            if (this.currentRole.id == role.id) {
                                this.selectedUsers = []
                                this.buildUserList()
                                this.updateRemoveUserButton()
                            }
                        }
        })
    }

    apiAddUserToRole(role, username) {
        this.apiCall({
            endpoint:   'user_capabilities/add_user_to_role',
            method:     'POST',
            data:       {
                            roleid: role.id,
                            username: username
                        },
            callback:   data => {
                            role.users = data
                            this.buildUserList()

                            let textField = document.getElementById('add-user-to-role-username')
                            textField.value = ''
                            this.updateRemoveUserButton()
                        }
        })
    }

    apiRenameRole(roleid, name) {
        this.apiCall({
            endpoint:   'user_capabilities/rename_role',
            method:     'POST',
            data:       {
                            roleid: roleid,
                            name: name
                        },
            callback:   data => {
                            this.findRole(roleid).name = data.name
                            this.buildRoleList()
                            this.updateActiveRole(this.currentRole)
                        }
        })
    }


    //
    // Utility functions
    //

    findRole(id) {
        return this.roles.find(role => role.id === id)
    }

    isRootUser(uid) {
        return uid === this.rootUserId
    }

    isSuperuserRole(role) {
        return role.id === 1
    }

    //
    // UI building functions
    //

    buildRoleList() {
        // Get the root element for the role list
        let roleList = document.querySelector('.roles')

        // Map roles onto an array of list items, and then append to list
        roleList.innerHTML = this.roles
            .map(role =>
                `<li class='role' data-role='${role.id}'>
                    <span class='role-name'>${role.name}</span>
                    ${role.id===1?'':"<span class='role-edit'><i class='icon-edit'></i></span>"}
                </li>`)
            .join('')

        // Set up event listeners to keep our model up to date
        roleList.querySelectorAll('.role').forEach(elem => {
            // Get the ID from the data attribute
            const id = parseInt(elem.getAttribute('data-role'), 10)

            // Add a click event to select the role
            elem.addEventListener('click', evt => {
                this.updateActiveRole(this.findRole(id))
            })
        })

        // Listen for the edit button
        roleList.querySelectorAll('.role-edit').forEach(elem => {
            const parent = elem.parentNode;
            const id = parseInt(parent.getAttribute('data-role'), 10)

            // Add a click event to select the role
            elem.addEventListener('click', evt => {
                let name = window.prompt(
                    'What do you want to rename this group to?',
                    this.findRole(id).name
                )

                if (name) {
                    this.apiRenameRole(id, name)
                }
            })
        })
    }

    buildUserList() {
        const users = this.currentRole.users
        if (!users) {
            return
        }

        // Explain who can't be removed
        const username = user =>
            (this.isSuperuserRole(this.currentRole) && this.isRootUser(user.uid)) ?
                `${user.username} <span class='text-muted'>(can't be removed)</span>` :
                `${user.username}`

        const disabled = user =>
            (this.isSuperuserRole(this.currentRole) && this.isRootUser(user.uid)) ?
                " disabled" :
                ""

        const userList = document.querySelector('.users')
        userList.innerHTML = users
            .map(user =>
                `<li>
                    <label class='users-label' for='user-${user.uid}'>
                        <input type='checkbox' id='user-${user.uid}' data-uid='${user.uid}' ${disabled(user)}>
                        <span class='users-name'>${username(user)}<span>
                    </label>
                </li>`)
            .join('')

        // Set up event listeners to keep our model up to date
        document.querySelectorAll('.users input').forEach(elem => {
            // Get the ID from the data attribute
            let id = parseInt(elem.getAttribute('data-uid'), 10)

            // Add a click event to add/remove from the state
            elem.addEventListener('click', evt => {
                if (elem.checked) {
                    if (!this.selectedUsers.includes(id)) {
                        this.selectedUsers.push(id)
                    }
                } else {
                    // Filter out this ID from the list
                    this.selectedUsers = this.selectedUsers.filter(selected => selected !== id)
                }

                this.updateRemoveUserButton()
            })
        })
    }

    initRoleAddButton() {
        // Set up the 'role add' button
        let addButton = document.querySelector('.panel--roles-add')
        addButton.addEventListener('click', evt => {
            let name = window.prompt('Please enter the group name')
            if (name !== null) {
                // We have a name!  Make the group
                this.apiCreateRole(name)
            }

        })

        addButton.disabled = false
    }

    initCapabilityUpdateButton() {
        let button = document.querySelector('.capabilities-update')

        button.addEventListener('click', evt => {
            evt.preventDefault()

            // Get the capabilities form elements
            const form = document.forms['form-capabilities']

            // Process them - take the checkboxes, remove the cap- prefix from the ID,
            // and then map it into an object like { capability: true, capability2: false }
            const elements = Array.from(form.elements)
                .filter(elem => elem.type === 'checkbox')
                .map(elem => ({
                    capability: elem.id.replace('cap-', ''),
                    setting: elem.checked
                }))
                .reduce((obj, row) => {
                    obj[row.capability] = row.setting
                    return obj
                }, {})

            // Call the API
            this.apiUpdateRole(this.currentRole.id, elements)
        })

        button.disabled = false
    }

    initUserRemoveButton() {
        this.buttons.removeUsersFromRole.addEventListener('click', evt => {
            evt.preventDefault()
            this.apiRemoveUsersFromRole(this.currentRole, this.selectedUsers)
        })

        this.updateRemoveUserButton()
    }

    initUserAddButton() {
        // Disable / undisable add button
        let textField = document.getElementById('add-user-to-role-username')
        textField.addEventListener('input', _ => this.updateUserAddButton())

        // Do something!
        this.buttons.addUserToRole.addEventListener('click', evt => {
            evt.preventDefault()
            this.apiAddUserToRole(this.currentRole, textField.value)
        })
    }

    //
    // UI updating functions
    //

    updateActiveRole(newRole) {
        const roleChanged = (newRole.id !== this.currentRole.id);
        this.currentRole = newRole

        for (let role of document.querySelectorAll('.roles li')) {
            // Make the current role active, not the others
            if (role.getAttribute('data-role') == this.currentRole.id) {
                role.classList.add('role--active')
            } else {
                role.classList.remove('role--active')
            }
        }

        if (roleChanged) {
            this.updateCheckedCapabilities()
            this.buildUserList()
            this.selectedUsers = []
        }
    }

    updateCheckedCapabilities() {
        // Reset all existing
        for (let input of document.querySelectorAll('.capabilities--input')) {
            input.checked = false
            input.disabled = false
        }

        // Populate capabilities
        for (let capability of this.currentRole.capabilities) {
            let input = document.getElementById('cap-' + capability)

            // Sometimes there are capabilities given to roles in the database,
            // but with no module using them.  So the input might not always exist.
            if (input) {
                input.checked = true
            }
        }

        // Disable capabilities_edit/_view if in superuser role
        if (this.isSuperuserRole(this.currentRole)) {
            document.querySelector("#cap-capabilities_edit").disabled = true
            document.querySelector("#cap-capabilities_view").disabled = true
        }
    }

    updateRemoveUserButton() {
        this.buttons.removeUsersFromRole.disabled = (this.selectedUsers.length === 0)
    }

    updateUserAddButton() {
        let textField = document.getElementById('add-user-to-role-username')

        this.buttons.addUserToRole.disabled = (textField.value.length === 0)
    }
}
