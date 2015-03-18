<?php

class DevHelper_Generator_Code_Installer
{
    public static function generate(array $addOn, DevHelper_Config_Base $config)
    {
        $className = self::getClassName($addOn, $config);

        $tables = array();
        $dataClasses = $config->getDataClasses();
        foreach ($dataClasses as $dataClass) {
            $table = array();
            $table['createQuery'] = DevHelper_Generator_Db::createTable($config, $dataClass);
            $table['dropQuery'] = DevHelper_Generator_Db::dropTable($config, $dataClass);

            $tables[$dataClass['name']] = $table;
        }
        $tables = DevHelper_Generator_File::varExport($tables);

        $patches = array();
        $dataPatches = $config->getDataPatches();
        foreach ($dataPatches as $table => $tablePatches) {
            foreach ($tablePatches as $dataPatch) {
                $patch = array();
                $patch['table'] = $table;
                $patch['field'] = $dataPatch['name'];
                $patch['showTablesQuery'] = DevHelper_Generator_Db::showTables($config, $table);
                $patch['showColumnsQuery'] = DevHelper_Generator_Db::showColumns($config, $table, $dataPatch);
                $patch['alterTableAddColumnQuery'] = DevHelper_Generator_Db::alterTableAddColumn($config, $table, $dataPatch);
                $patch['alterTableDropColumnQuery'] = DevHelper_Generator_Db::alterTableDropColumn($config, $table, $dataPatch);

                $patches[] = $patch;
            }
        }
        $patches = DevHelper_Generator_File::varExport($patches);

        $commentAutoGeneratedStart = DevHelper_Generator_File::COMMENT_AUTO_GENERATED_START;
        $commentAutoGeneratedEnd = DevHelper_Generator_File::COMMENT_AUTO_GENERATED_END;

        $contents = <<<EOF
<?php

class $className
{
    $commentAutoGeneratedStart

    protected static \$_tables = $tables;
    protected static \$_patches = $patches;

    public static function install(\$existingAddOn, \$addOnData)
    {
        \$db = XenForo_Application::get('db');

        foreach (self::\$_tables as \$table) {
            \$db->query(\$table['createQuery']);
        }

        foreach (self::\$_patches as \$patch) {
            \$tableExisted = \$db->fetchOne(\$patch['showTablesQuery']);
            if (empty(\$tableExisted)) {
                continue;
            }

            \$existed = \$db->fetchOne(\$patch['showColumnsQuery']);
            if (empty(\$existed)) {
                \$db->query(\$patch['alterTableAddColumnQuery']);
            }
        }

        self::installCustomized(\$existingAddOn, \$addOnData);
    }

    public static function uninstall()
    {
        \$db = XenForo_Application::get('db');

        foreach (self::\$_patches as \$patch) {
            \$tableExisted = \$db->fetchOne(\$patch['showTablesQuery']);
            if (empty(\$tableExisted)) {
                continue;
            }

            \$existed = \$db->fetchOne(\$patch['showColumnsQuery']);
            if (!empty(\$existed)) {
                \$db->query(\$patch['alterTableDropColumnQuery']);
            }
        }

        foreach (self::\$_tables as \$table){
            \$db->query(\$table['dropQuery']);
        }

        self::uninstallCustomized();
    }

    $commentAutoGeneratedEnd

    public static function installCustomized(\$existingAddOn, \$addOnData)
    {
        // customized install script goes here
    }

    public static function uninstallCustomized()
    {
        // customized uninstall script goes here
    }

}
EOF;

        return array(
            $className,
            $contents
        );
    }

    public static function getClassName(array $addOn, DevHelper_Config_Base $config)
    {
        return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'Installer');
    }

}
