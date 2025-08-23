# Litepie Teams 🚀

[![Latest Version on Packagist](https://img.shields.io/packagist/v/litepie/teams.svg?style=flat-square)](https://packagist.org/packages/litepie/teams)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/litepie/teams/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/litepie/teams/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/litepie/teams/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/litepie/teams/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/litepie/teams.svg?style=flat-square)](https://packagist.org/packages/litepie/teams)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

A modern, tenant-ready teams management package for Laravel 12+ applications. Built for collaboration, scalability, and multi-tenancy from the ground up.

> **Note**: This package is part of the Litepie ecosystem and is open source under the MIT license. Perfect for SaaS applications, multi-tenant platforms, and collaborative tools.

## ✨ Features

- 🏢 **Multi-Tenant Ready**: Complete tenant isolation with [Litepie Tenancy](https://github.com/Litepie/Tenancy)
- 👥 **Team Management**: Create, manage, and organize teams with hierarchical structures
- 🛡️ **Role-Based Access**: Integration with [Litepie Shield](https://github.com/Litepie/Shield) for granular permissions
- 🔄 **Workflow Integration**: Team workflows with [Litepie Flow](https://github.com/Litepie/Flow)
- ⚡ **Action Pattern**: Built on [Litepie Actions](https://github.com/Litepie/Actions) for clean business logic
- 📁 **File Management**: Team file sharing with [Litepie Filehub](https://github.com/Litepie/Filehub)
- 🚀 **Modern Laravel**: Laravel 12+ ready with PHP 8.2+ support
- 🎨 **Rich API**: RESTful API with comprehensive resources
- 📊 **Team Analytics**: Performance metrics and insights
- 🔔 **Real-time Events**: WebSocket support for live collaboration
- 🧪 **Fully Tested**: Comprehensive test suite with 95%+ coverage

## 📋 Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
- [Multi-Tenant Usage](#multi-tenant-usage)
- [Workflows](#workflows)
- [Permissions & Roles](#permissions--roles)
- [File Management](#file-management)
- [API Integration](#api-integration)
- [Events](#events)
- [Testing](#testing)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [Security](#security)
- [License](#license)

## 🚀 Installation

Install the package via Composer:

```bash
composer require litepie/teams
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="teams-migrations"
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="teams-config"
```

## ⚙️ Configuration

The configuration file `config/teams.php` provides extensive customization options:

```php
return [
    'model' => [
        'team' => \Litepie\Teams\Models\Team::class,
        'team_member' => \Litepie\Teams\Models\TeamMember::class,
        'team_invitation' => \Litepie\Teams\Models\TeamInvitation::class,
    ],
    
    'features' => [
        'tenancy' => true,
        'workflows' => true,
        'file_management' => true,
        'real_time_events' => true,
        'team_analytics' => true,
    ],
    
    'permissions' => [
        'auto_create' => true,
        'middleware' => ['team.member'],
    ],
    
    'invitations' => [
        'expires_after_days' => 7,
        'max_pending_per_team' => 50,
    ],
];
```

## 🚀 Quick Start

### 1. Setup Your User Model

Add the `HasTeams` trait to your User model:

```php
use Litepie\Teams\Traits\HasTeams;

class User extends Authenticatable
{
    use HasTeams;
    
    // Your existing model code
}
```

### 2. Create Your First Team

```php
use Litepie\Teams\Actions\CreateTeamAction;

$team = CreateTeamAction::execute(null, [
    'name' => 'Development Team',
    'description' => 'Our amazing development team',
    'type' => 'development',
    'settings' => [
        'visibility' => 'private',
        'features' => ['file_sharing', 'workflows'],
        'limits' => ['max_members' => 50],
    ],
], $user);
```

### 3. Add Team Members

```php
use Litepie\Teams\Actions\AddTeamMemberAction;

// Add a member with specific role
AddTeamMemberAction::execute($team, [
    'user_id' => $user->id,
    'role' => 'member',
    'permissions' => ['view_team', 'create_posts'],
], $teamOwner);

// Invite via email
use Litepie\Teams\Actions\InviteTeamMemberAction;

InviteTeamMemberAction::execute($team, [
    'email' => 'newmember@example.com',
    'role' => 'member',
    'message' => 'Welcome to our team!',
], $teamOwner);
```

## 🏢 Multi-Tenant Usage

Teams seamlessly integrates with tenant-based applications:

```php
// Set tenant context
tenancy()->initialize($tenant);

// All team operations are now scoped to the current tenant
$teams = Team::current()->get(); // Only returns current tenant's teams

// Create tenant-specific team
$team = CreateTeamAction::execute(null, [
    'name' => 'Tenant Development Team',
    'tenant_id' => tenancy()->current()?->id,
], $user);

// Team permissions are automatically tenant-scoped
$user->can('manage', $team); // Checks within current tenant context
```

## 🔄 Workflows

Integrate team workflows for automated processes:

```php
use Litepie\Teams\Workflows\TeamWorkflow;

// Team lifecycle workflow
$workflow = TeamWorkflow::create();

// Available states: draft, active, suspended, archived
// Available transitions: activate, suspend, archive, restore

// Transition team through workflow
$team->transitionTo('active', [
    'activated_by' => $user->id,
    'reason' => 'Team setup completed',
]);

// Check current state
if ($team->getCurrentState()->getName() === 'active') {
    // Team is active
}
```

## 🛡️ Permissions & Roles

Teams integrates with Litepie Shield for comprehensive permission management:

```php
// Team-level permissions
$user->givePermissionTo('manage_team_members', $team);
$user->hasPermissionTo('edit_team_settings', $team);

// Role-based team access
$team->assignMemberRole($user, 'admin');
$team->assignMemberRole($user, 'member');

// Check team-specific permissions
if ($user->can('manage', $team)) {
    // User can manage this team
}

// Blade directives for teams
@teamPermission('edit_settings', $team)
    <button>Edit Team Settings</button>
@endteamPermission

@teamRole('admin', $team)
    <div class="admin-panel">Admin Controls</div>
@endteamRole
```

## 📁 File Management

Teams provides integrated file management through Litepie Filehub:

```php
// Upload team files
$team->attachFile($request->file('document'), 'documents', [
    'uploader_id' => $user->id,
    'title' => 'Team Charter',
    'visibility' => 'team_members',
]);

// Get team files
$documents = $team->getFiles('documents');
$avatars = $team->getFiles('avatars');

// File permissions
$file = $team->getFirstFile('logo');
if ($user->can('download', $file)) {
    return $file->downloadResponse();
}
```

## 🌐 API Integration

Teams provides a comprehensive RESTful API:

```php
// API routes are automatically registered
Route::middleware(['api', 'tenant.required'])->group(function () {
    Route::apiResource('teams', TeamController::class);
    Route::apiResource('teams.members', TeamMemberController::class);
    Route::apiResource('teams.invitations', TeamInvitationController::class);
});

// API Resources
$team = Team::find(1);
return new TeamResource($team);
// Returns: team data with members, files, permissions, etc.
```

## 📊 Events

Teams fires comprehensive events for real-time features:

```php
// Listen to team events
Event::listen(TeamCreated::class, function ($event) {
    // Send welcome notifications
    NotifyTeamCreated::dispatch($event->team);
});

Event::listen(MemberJoinedTeam::class, function ($event) {
    // Update team analytics
    UpdateTeamMetrics::dispatch($event->team);
});

// Real-time broadcasting
class MemberJoinedTeam implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return new PrivateChannel("team.{$this->team->id}");
    }
}
```

## 🧪 Testing

Teams includes comprehensive testing utilities:

```php
use Litepie\Teams\Testing\TeamTestHelpers;

class TeamFeatureTest extends TestCase
{
    use TeamTestHelpers;
    
    public function test_user_can_create_team()
    {
        $user = User::factory()->create();
        $tenant = $this->createTenant();
        
        $this->actingAsTenant($tenant)
             ->actingAs($user);
        
        $team = $this->createTeam([
            'name' => 'Test Team',
            'owner_id' => $user->id,
        ]);
        
        $this->assertDatabaseHas('teams', [
            'name' => 'Test Team',
            'tenant_id' => $tenant->id,
        ]);
        
        $this->assertTrue($user->ownsTeam($team));
    }
}
```

## 📈 Advanced Features

### Team Analytics

```php
use Litepie\Teams\Analytics\TeamAnalytics;

$analytics = TeamAnalytics::for($team)
    ->period('last_30_days')
    ->metrics(['activity', 'files', 'members'])
    ->get();

// Get insights
$insights = $team->getAnalytics([
    'member_activity' => true,
    'file_usage' => true,
    'collaboration_metrics' => true,
]);
```

### Team Templates

```php
use Litepie\Teams\Templates\TeamTemplate;

// Create team from template
$template = TeamTemplate::find('development_team');
$team = $template->createTeam([
    'name' => 'New Dev Team',
    'owner_id' => $user->id,
]);

// Templates include predefined roles, permissions, and workflows
```

### Bulk Operations

```php
use Litepie\Teams\Actions\BulkTeamOperationsAction;

// Bulk member operations
BulkTeamOperationsAction::execute($team, [
    'operation' => 'add_members',
    'users' => [$user1->id, $user2->id, $user3->id],
    'role' => 'member',
]);

// Bulk permission updates
BulkTeamOperationsAction::execute($team, [
    'operation' => 'update_permissions',
    'members' => [$user1->id, $user2->id],
    'permissions' => ['edit_content', 'manage_files'],
]);
```

## 🗺️ Roadmap

We're continuously improving Litepie Teams. Here's what's coming next:

### 🚀 Upcoming Features (v2.1)
- [ ] Team templates and blueprints
- [ ] Advanced team analytics dashboard
- [ ] Integration with popular project management tools
- [ ] Team chat integration
- [ ] Mobile SDK for teams

### 🔮 Future Releases (v2.2+)
- [ ] AI-powered team insights
- [ ] Advanced workflow automation
- [ ] Team performance metrics
- [ ] Custom team widgets
- [ ] GraphQL API support

Want to contribute to these features? Check out our [contributing guide](#contributing)!

## 🤝 Contributing

We love contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on:

- 🐛 **Bug Reports**: How to report bugs effectively
- 💡 **Feature Requests**: Proposing new features
- 🔧 **Pull Requests**: Code contribution guidelines
- 📝 **Documentation**: Improving our docs
- 🧪 **Testing**: Adding and running tests

### Development Setup

1. **Fork and Clone**
   ```bash
   git clone https://github.com/your-username/teams.git
   cd teams
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup Testing Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --env=testing
   ```

4. **Run Tests**
   ```bash
   composer test
   composer test:coverage
   ```

### Code Style

We follow PSR-12 coding standards:

```bash
composer format        # Fix code style
composer analyse       # Run static analysis
composer test:types    # Check type coverage
```

## 📋 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

Major releases and their highlights:

- **v2.0.0** - Multi-tenancy support, workflow integration
- **v1.5.0** - File management, advanced permissions
- **v1.0.0** - Initial stable release

## 🔒 Security

If you discover any security-related issues, please email security@renfos.com instead of using the issue tracker. All security vulnerabilities will be promptly addressed.

### Security Features

- 🛡️ **Tenant Isolation**: Complete data separation between tenants
- 🔐 **Permission System**: Granular role-based access control
- 🔍 **Audit Logging**: Complete activity tracking
- 🚫 **Input Validation**: Comprehensive request validation
- 🔒 **File Security**: Secure file uploads and access controls

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

This package is open source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Credits

- **Created by**: [Renfos Technologies](https://renfos.com)
- **Maintained by**: [Litepie Development Team](https://github.com/litepie)
- **Contributors**: [All Contributors](../../contributors)

Special thanks to all the developers who have contributed to making this package better!

## 📞 Support & Community

- **📖 Documentation**: [teams.litepie.com](https://teams.litepie.com)
- **🐛 Bug Reports**: [GitHub Issues](https://github.com/litepie/teams/issues)
- **💬 Discussions**: [GitHub Discussions](https://github.com/litepie/teams/discussions)
- **📧 Email Support**: [support@renfos.com](mailto:support@renfos.com)
- **💼 Commercial Support**: [Contact Us](https://renfos.com/contact)

### Community Guidelines

Please be respectful and constructive in all interactions. See our [Code of Conduct](CODE_OF_CONDUCT.md) for more details.

## 🌟 Sponsors

This project is maintained by [Renfos Technologies](https://renfos.com) and supported by our amazing sponsors:

- Want to sponsor this project? [Become a sponsor](https://github.com/sponsors/litepie)

---

<div align="center">

**Built with ❤️ by the [Renfos Technologies](https://renfos.com) Team**

*Litepie Teams - Where collaboration meets innovation.*

[⭐ Star us on GitHub](https://github.com/litepie/teams) | [🐦 Follow us on Twitter](https://twitter.com/litepie) | [💼 Visit Renfos](https://renfos.com)

</div>
