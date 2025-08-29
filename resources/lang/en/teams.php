<?php

return [
    'team' => 'Team',
    'teams' => 'Teams',
    'name' => 'Name',
    'description' => 'Description',
    'status' => 'Status',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    
    'actions' => [
        'create' => 'Create Team',
        'edit' => 'Edit Team',
        'delete' => 'Delete Team',
        'view' => 'View Team',
        'archive' => 'Archive Team',
        'restore' => 'Restore Team',
        'suspend' => 'Suspend Team',
        'activate' => 'Activate Team',
    ],
    
    'members' => [
        'add' => 'Add Member',
        'remove' => 'Remove Member',
        'invite' => 'Invite Member',
        'role' => 'Role',
        'joined_at' => 'Joined At',
    ],
    
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
        'suspended' => 'Suspended',
        'pending' => 'Pending',
    ],
    
    'roles' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'moderator' => 'Moderator',
        'member' => 'Member',
    ],
    
    'messages' => [
        'created' => 'Team created successfully.',
        'updated' => 'Team updated successfully.',
        'deleted' => 'Team deleted successfully.',
        'member_added' => 'Member added successfully.',
        'member_removed' => 'Member removed successfully.',
        'invitation_sent' => 'Invitation sent successfully.',
        'invitation_accepted' => 'Invitation accepted successfully.',
        'invitation_declined' => 'Invitation declined.',
    ],
    
    'errors' => [
        'not_found' => 'Team not found.',
        'access_denied' => 'Access denied.',
        'already_member' => 'User is already a member of this team.',
        'not_member' => 'User is not a member of this team.',
        'invalid_invitation' => 'Invalid or expired invitation.',
        'cannot_remove_owner' => 'Cannot remove team owner.',
    ],
];
