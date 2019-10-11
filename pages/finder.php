<?php

$config = rex_config::get('yduplicate');

$function = rex_request('function', 'string', '');
$data = rex_request('data', 'int', 0);
$other = rex_request('other', 'int', 0);
$id_1 = rex_request('id_1', 'int', 0);
$id_2 = rex_request('id_2', 'int', 0);

$table = $config['table'];
$fields = explode(',', $config['fields']);
$fields_view = explode(',', $config['fields_view']);
$detailUrl = $config['url'];

if (empty($table) || empty($fields)) {
    echo rex_view::error(rex_i18n::msg('yduplicate_finder_error'));
    return;
}

switch ($function) {
    case 'merge':
        $sql = rex_sql::factory();
        $sql->setQuery('DELETE FROM '.$table. ' WHERE id=?', [$other]);
        $sql->setQuery('UPDATE '.rex::getTable('yduplicate_non_duplicate').' SET id_1 = REPLACE(id_1, :other, :data) WHERE `table` = :table AND  id_1 = :other', ['table' => $table, 'other' => $other, 'data' => $data]);
        $sql->setQuery('UPDATE '.rex::getTable('yduplicate_non_duplicate').' SET id_2 = REPLACE(id_2, :other, :data) WHERE `table` = :table AND  id_2 = :other', ['table' => $table, 'other' => $other, 'data' => $data]);
        break;
        break;
    case 'non_duplicate':
        if ($id_1 > $id_2) {
            $id = $id_2;
            $id_2 = $id_1;
            $id_1 = $id;
        }
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('yduplicate_non_duplicate'));
        $sql->setValue('table', $table);
        $sql->setValue('id_1', $id_1);
        $sql->setValue('id_2', $id_2);
        $sql->insertOrUpdate();
        break;
}





$sqlGroupBy = [];
$sql = rex_sql::factory();
foreach ($fields as $field) {
    $field = $sql->escapeIdentifier(trim($field));
    $sqlGroupBy[] = $field;
}

$columns = array_keys(rex_sql_table::get($table)->getColumns());

if (!empty($fields_view[0])) {
    foreach ($columns as $index => $column) {
        if ($column == 'id' || in_array($column, $fields_view)) {
            continue;
        }

        unset($columns[$index]);
    }
}

$data = $sql->getArray(
    sprintf('
        SELECT  count(*) AS count, 
                GROUP_CONCAT(id ORDER BY id SEPARATOR ",") AS ids 
        FROM    %s 
        GROUP BY %s 
        HAVING  count > 1'
        , $table, implode(', ', $sqlGroupBy)
    )
);

$nonDuplicates = $sql->getArray('SELECT `id_1`, `id_2` FROM '.rex::getTable('yduplicate_non_duplicate').' WHERE `table` = :table', ['table' => $table]);

$allIds = [];
$duplicates = [];
foreach ($data as $row) {
    $ids = array_map('intval', explode(',', $row['ids']));
    for ($i = 0; $i < $row['count'] - 1; ++$i) {
        for ($j = $i + 1; $j < $row['count']; ++$j) {
            $id1 = $ids[$i];
            $id2 = $ids[$j];
            foreach ($nonDuplicates as $nonDuplicate) {
                if (
                    intval($nonDuplicate['id_1']) === $id1 && intval($nonDuplicate['id_2']) === $id2 ||
                    intval($nonDuplicate['id_1']) === $id2 && intval($nonDuplicate['id_2']) === $id1
                ) {
                    continue 2;
                }
            }
            $duplicates[] = ['id_1' => $id1, 'id_2' => $id2];
            $allIds[$id1] = $id1;
            $allIds[$id2] = $id2;
        }
    }
}

if (!count($allIds)) {
    echo rex_view::info(rex_i18n::msg('yduplicate_finder_no_duplicates'));
    return;
}

$where = [];
foreach ($allIds as $id) {
    $where[] = 'id = "'.$id.'"';
}
$items = $sql->getArray('SELECT * FROM '.$table.' WHERE '.implode(' OR ', $where));
$itemsSave = [];
foreach ($items as $item) {
    $itemsSave[$item['id']] = $item;
}

foreach ($duplicates as &$duplicate) {
    $duplicate['id_1'] = $itemsSave[$duplicate['id_1']];
    $duplicate['id_2'] = $itemsSave[$duplicate['id_2']];
}
unset($duplicate);


$getRow = static function ($data, $other, $firstRow = false) use($columns, $detailUrl) {
    $row = '';
    foreach ($columns as $column) {
        $row .= '<td>'.$data[$column].'</td>';
    }

    $row .= '
        <td class="actions text-nowrap">
            <a href="'.rex_url::backendController().'?'.(str_replace('{{id}}', $data['id'], $detailUrl)).'" class="btn btn-primary btn-xs">
                '.rex_i18n::msg('yduplicate_detail').'
            </a>
            <a href="'.rex_url::currentBackendPage(['function' => 'merge', 'data' => $data['id'], 'other' => $other['id']]).'" class="btn btn-warning btn-xs">
                '.rex_i18n::msg('yduplicate_merge').'
            </a>
        </td>';
        if ($firstRow) {
            $row .= '<td class="actions vertical-middle" rowspan="2">
                <a href="'.rex_url::currentBackendPage(['function' => 'non_duplicate', 'id_1' => $data['id'], 'id_2' => $other['id']]).'" class="btn btn-warning btn-xs">
                    '.rex_i18n::msg('yduplicate_non_duplicate').'
                </a>
            </td>';
        }

    return '<tr>'.$row.'</tr>';
};

$thead = '';
foreach ($columns as $column) {
    $thead .= '<th>'.$column.'</th>';
}

$tbody = '';
foreach ($duplicates as $index => $duplicate) {
    $tbody .= '
        <tr class="separator">
            <td colspan="'.(count($columns) + 2).'">&nbsp;</td>
        </tr>';
    $tbody .= $getRow($duplicate['id_1'], $duplicate['id_2'], true);
    $tbody .= $getRow($duplicate['id_2'], $duplicate['id_1']);
}
$tbody .= '
    <tr class="separator">
        <td colspan="'.(count($columns) + 2).'">&nbsp;</td>
    </tr>';

echo '
        <table class="table">
            <thead>
                <tr>
                    '.$thead.'
                    <th colspan="2"></th>
                </tr>
            </thead>
            <tbody>
                '.$tbody.'                    
            </tbody>
        </table>
';
