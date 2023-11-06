<?php

class AddEvaluatorRole extends Migration {

    function description()
    {
        return 'create role CoursewareGPTEvaluator for access to evaluation functions';
    }
    public function up()
    {
        $role = new Role();
        $role->setRolename('CoursewareGPTEvaluator');
        RolePersistence::saveRole($role);
    }

    public function down()
    {
        $role_id = RolePersistence::getRoleIdByName('CoursewareGPTEvaluator');
        RolePersistence::deleteRole(new Role($role_id));
    }
}
