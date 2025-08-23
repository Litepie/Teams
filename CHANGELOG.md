# Changelog

All notable changes to `litepie/teams` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Nothing yet

### Changed
- Nothing yet

### Deprecated
- Nothing yet

### Removed
- Nothing yet

### Fixed
- Nothing yet

### Security
- Nothing yet

## [2.0.0] - 2024-01-15

### Added
- 🎉 Complete rewrite for Laravel 12.x compatibility
- 🏢 **Multi-tenancy support** with Litepie Tenancy integration
- 👥 **Team Management System** with comprehensive member roles
- 📧 **Team Invitations** with email notifications and expiration
- 🔄 **Action-based Architecture** with 9 core actions
- 📊 **Team Analytics** and reporting capabilities
- 🔔 **Event System** with 8 domain events
- 🌊 **Workflow Integration** with Litepie Flow
- 🛡️ **Security Features** with activity logging
- 🔍 **Advanced Search** and filtering
- 📱 **API Resources** for modern frontend integration
- 🎨 **Blade Components** for rapid UI development
- 🧪 **Comprehensive Test Suite** with Pest PHP
- 📚 **Rich Documentation** and examples

### Team Models
- `Team` - Core team model with multi-tenancy
- `TeamMember` - Member management with roles and permissions
- `TeamInvitation` - Invitation system with expiration

### Actions
- `CreateTeamAction` - Create new teams with validation
- `UpdateTeamAction` - Update team information
- `DeleteTeamAction` - Soft delete teams with cleanup
- `AddMemberAction` - Add members with role assignment
- `RemoveMemberAction` - Remove members safely
- `UpdateMemberRoleAction` - Change member roles
- `InviteUserAction` - Send team invitations
- `AcceptInvitationAction` - Process invitation acceptance
- `CancelInvitationAction` - Cancel pending invitations

### Events
- `TeamCreated` - Fired when team is created
- `TeamUpdated` - Fired when team is updated
- `TeamDeleted` - Fired when team is deleted
- `MemberAdded` - Fired when member joins
- `MemberRemoved` - Fired when member leaves
- `MemberRoleUpdated` - Fired when role changes
- `InvitationSent` - Fired when invitation is sent
- `InvitationAccepted` - Fired when invitation is accepted

### API Features
- RESTful API endpoints for all operations
- JSON API specification compliance
- Rate limiting and throttling
- API authentication with Sanctum
- Comprehensive error handling
- Request validation with form requests

### Frontend Integration
- Vue.js components for team management
- React components (optional)
- Livewire components for real-time updates
- Alpine.js integration for progressive enhancement
- Responsive design with Tailwind CSS

### Database Features
- Optimized database schema with proper indexes
- Soft deletes with cascade handling
- UUID primary keys for security
- Polymorphic relationships for flexibility
- Database seeders and factories

### Security
- **Policy-based Authorization** with TeamPolicy
- **Activity Logging** with Litepie Shield integration
- **Input Sanitization** and validation
- **CSRF Protection** on all forms
- **Rate Limiting** on sensitive operations
- **Secure Token Generation** for invitations

### Performance
- **Database Query Optimization** with eager loading
- **Caching Strategy** for frequently accessed data
- **Queue Integration** for heavy operations
- **Pagination** for large datasets
- **Lazy Loading** for relationships

### Developer Experience
- **Comprehensive Documentation** with examples
- **Test Coverage** > 90%
- **Code Quality** with PHPStan level 8
- **Modern PHP 8.2+** features and syntax
- **IDE Support** with accurate type hints
- **Debugging Tools** with detailed error messages

### Breaking Changes
- 🚨 **PHP 8.2+ Required** - Dropped support for older PHP versions
- 🚨 **Laravel 12+ Required** - Not compatible with older Laravel versions
- 🚨 **Database Schema Changes** - New migration required
- 🚨 **API Response Format** - Updated to JSON API specification
- 🚨 **Configuration Changes** - New config file structure
- 🚨 **Namespace Changes** - Updated to Litepie\Teams

### Migration Guide
- Run `php artisan migrate` for database updates
- Update configuration files from `config/team.php` to `config/teams.php`
- Update namespaces from old structure to `Litepie\Teams`
- Review policy changes for authorization
- Update frontend components for new API format

### Dependencies
- `php: ^8.2`
- `laravel/framework: ^12.0`
- `litepie/tenancy: ^2.0`
- `litepie/database: ^2.0`
- `litepie/actions: ^2.0`
- `litepie/flow: ^2.0`
- `litepie/filehub: ^2.0`
- `litepie/shield: ^2.0`

## [1.x] - Legacy Version

### Note
Version 1.x was the legacy implementation. This changelog starts with version 2.0.0 as it represents a complete rewrite of the package.

For legacy version history, please refer to the git commit history.

---

## Release Notes Format

### Types of Changes
- **Added** for new features
- **Changed** for changes in existing functionality
- **Deprecated** for soon-to-be removed features
- **Removed** for now removed features
- **Fixed** for any bug fixes
- **Security** for vulnerability fixes

### Emoji Guide
- 🎉 Major new features
- ✨ Minor new features
- 🐛 Bug fixes
- 🔒 Security improvements
- 📚 Documentation updates
- 🎨 UI/UX improvements
- ⚡ Performance improvements
- 🚨 Breaking changes
- 🗑️ Deprecations
- 🔧 Configuration changes
