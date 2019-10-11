<?php

rex_sql_table::get(rex::getTable('yduplicate_non_duplicate'))
    ->ensureColumn(new rex_sql_column('id', 'int(11) unsigned', false, null, 'AUTO_INCREMENT'))
    ->ensureColumn(new rex_sql_column('table', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('id_1', 'int(11)'))
    ->ensureColumn(new rex_sql_column('id_2', 'int(11)'))
    ->setPrimaryKey(['id'])
    ->ensure();
