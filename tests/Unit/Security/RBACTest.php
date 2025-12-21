<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\Role;
use Ksfraser\Security\AuthorizationManager;

/**
 * RBACTest - Tests for Role-Based Access Control
 * 
 * @package    Tests\Unit\Security
 * @since      20251221
 */
class RBACTest extends TestCase
{
    private AuthorizationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new AuthorizationManager();

        // Define standard roles
        $admin = new Role('admin', 'Administrator');
        $admin->addPermission('create_loan')
              ->addPermission('edit_loan')
              ->addPermission('delete_loan')
              ->addPermission('view_reports')
              ->addPermission('manage_users');

        $user = new Role('user', 'Regular User');
        $user->addPermission('create_loan')
             ->addPermission('view_loan');

        $viewer = new Role('viewer', 'Viewer');
        $viewer->addPermission('view_loan')
               ->addPermission('view_reports');

        $this->manager->defineRole($admin);
        $this->manager->defineRole($user);
        $this->manager->defineRole($viewer);
    }

    /**
     * Test role creation
     */
    public function testRoleCreation()
    {
        $role = new Role('manager', 'Manager');
        $role->addPermission('approve_loan');

        $this->assertEquals('manager', $role->getId());
        $this->assertEquals('Manager', $role->getName());
        $this->assertTrue($role->hasPermission('approve_loan'));
    }

    /**
     * Test role permission management
     */
    public function testRolePermissionManagement()
    {
        $role = new Role('test', 'Test');

        $this->assertFalse($role->hasPermission('test_perm'));

        $role->addPermission('test_perm');
        $this->assertTrue($role->hasPermission('test_perm'));

        $role->removePermission('test_perm');
        $this->assertFalse($role->hasPermission('test_perm'));
    }

    /**
     * Test user role assignment
     */
    public function testUserRoleAssignment()
    {
        $this->manager->assignRoleToUser('user1', 'admin');
        $this->manager->assignRoleToUser('user1', 'user');

        $roles = $this->manager->getUserRoles('user1');

        $this->assertCount(2, $roles);
        $this->assertContains('admin', $roles);
        $this->assertContains('user', $roles);
    }

    /**
     * Test permission checking for admin
     */
    public function testAdminPermissions()
    {
        $this->manager->assignRoleToUser('admin_user', 'admin');

        $this->assertTrue($this->manager->hasPermission('admin_user', 'create_loan'));
        $this->assertTrue($this->manager->hasPermission('admin_user', 'delete_loan'));
        $this->assertTrue($this->manager->hasPermission('admin_user', 'manage_users'));
    }

    /**
     * Test permission checking for regular user
     */
    public function testUserPermissions()
    {
        $this->manager->assignRoleToUser('regular_user', 'user');

        $this->assertTrue($this->manager->hasPermission('regular_user', 'create_loan'));
        $this->assertTrue($this->manager->hasPermission('regular_user', 'view_loan'));
        $this->assertFalse($this->manager->hasPermission('regular_user', 'delete_loan'));
        $this->assertFalse($this->manager->hasPermission('regular_user', 'manage_users'));
    }

    /**
     * Test permission checking for viewer
     */
    public function testViewerPermissions()
    {
        $this->manager->assignRoleToUser('viewer_user', 'viewer');

        $this->assertTrue($this->manager->hasPermission('viewer_user', 'view_loan'));
        $this->assertTrue($this->manager->hasPermission('viewer_user', 'view_reports'));
        $this->assertFalse($this->manager->hasPermission('viewer_user', 'create_loan'));
        $this->assertFalse($this->manager->hasPermission('viewer_user', 'delete_loan'));
    }

    /**
     * Test role removal from user
     */
    public function testRoleRemovalFromUser()
    {
        $this->manager->assignRoleToUser('multi_user', 'admin');
        $this->manager->assignRoleToUser('multi_user', 'user');

        $this->assertTrue($this->manager->hasPermission('multi_user', 'delete_loan'));

        $this->manager->removeRoleFromUser('multi_user', 'admin');

        $this->assertFalse($this->manager->hasPermission('multi_user', 'delete_loan'));
        $this->assertTrue($this->manager->hasPermission('multi_user', 'create_loan'));
    }

    /**
     * Test access logging
     */
    public function testAccessLogging()
    {
        $this->manager->assignRoleToUser('tracked_user', 'user');

        // Perform access checks
        $this->manager->hasPermission('tracked_user', 'create_loan'); // Granted
        $this->manager->hasPermission('tracked_user', 'delete_loan'); // Denied
        $this->manager->hasPermission('tracked_user', 'view_loan');   // Granted

        $log = $this->manager->getAccessLog();

        $this->assertCount(3, $log);
        $this->assertTrue($log[0]['granted']);
        $this->assertFalse($log[1]['granted']);
        $this->assertTrue($log[2]['granted']);
    }

    /**
     * Test access log limit
     */
    public function testAccessLogLimit()
    {
        $this->manager->assignRoleToUser('busy_user', 'admin');

        // Generate 50 access checks
        for ($i = 0; $i < 50; $i++) {
            $this->manager->hasPermission('busy_user', 'create_loan');
        }

        $fullLog = $this->manager->getAccessLog();
        $limitedLog = $this->manager->getAccessLog(10);

        $this->assertCount(50, $fullLog);
        $this->assertCount(10, $limitedLog);
    }

    /**
     * Test invalid role assignment
     */
    public function testInvalidRoleAssignment()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->assignRoleToUser('user', 'nonexistent_role');
    }

    /**
     * Test clear access log
     */
    public function testClearAccessLog()
    {
        $this->manager->assignRoleToUser('user', 'admin');
        $this->manager->hasPermission('user', 'create_loan');

        $log = $this->manager->getAccessLog();
        $this->assertNotEmpty($log);

        $this->manager->clearAccessLog();

        $log = $this->manager->getAccessLog();
        $this->assertEmpty($log);
    }

    /**
     * Test statistics
     */
    public function testStatistics()
    {
        $this->manager->assignRoleToUser('stat_user', 'admin');
        $this->manager->hasPermission('stat_user', 'create_loan'); // Grant
        $this->manager->hasPermission('stat_user', 'invalid'); // Deny

        $stats = $this->manager->getStatistics();

        $this->assertGreaterThan(0, $stats['total_roles']);
        $this->assertGreaterThan(0, $stats['total_users']);
        $this->assertEquals(2, $stats['total_access_checks']);
        $this->assertEquals(1, $stats['access_granted']);
        $this->assertEquals(1, $stats['access_denied']);
    }

    /**
     * Test get role
     */
    public function testGetRole()
    {
        $role = $this->manager->getRole('admin');

        $this->assertNotNull($role);
        $this->assertEquals('admin', $role->getId());
        $this->assertTrue($role->hasPermission('manage_users'));
    }

    /**
     * Test get all roles
     */
    public function testGetAllRoles()
    {
        $roles = $this->manager->getAllRoles();

        $this->assertCount(3, $roles); // admin, user, viewer
        $this->assertArrayHasKey('admin', $roles);
        $this->assertArrayHasKey('user', $roles);
        $this->assertArrayHasKey('viewer', $roles);
    }

    /**
     * Test multiple roles grant permission
     */
    public function testMultipleRolesGrant()
    {
        $this->manager->assignRoleToUser('multi_role_user', 'user');
        $this->manager->assignRoleToUser('multi_role_user', 'viewer');

        // User role allows this
        $this->assertTrue($this->manager->hasPermission('multi_role_user', 'create_loan'));
        // Viewer role allows this
        $this->assertTrue($this->manager->hasPermission('multi_role_user', 'view_reports'));
    }

    /**
     * Test fluent permission building
     */
    public function testFluentPermissionBuilding()
    {
        $role = new Role('custom', 'Custom Role');
        $result = $role->addPermission('perm1')
                       ->addPermission('perm2')
                       ->addPermission('perm3');

        // Check fluent returns self
        $this->assertInstanceOf(Role::class, $result);

        // Check all permissions added
        $this->assertTrue($role->hasPermission('perm1'));
        $this->assertTrue($role->hasPermission('perm2'));
        $this->assertTrue($role->hasPermission('perm3'));
    }
}
