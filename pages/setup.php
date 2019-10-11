<?php

$form = rex_config_form::factory('yduplicate');

$field = $form->addTextField('table', null, ['class' => 'form-control']);
$field->setLabel(rex_i18n::msg('yduplicate_table'));

$field = $form->addTextField('fields', null, ['class' => 'form-control']);
$field->setLabel(rex_i18n::msg('yduplicate_fields'));
$field->setNotice(rex_i18n::msg('yduplicate_comma_separated'));

$field = $form->addTextField('fields_view', null, ['class' => 'form-control']);
$field->setLabel(rex_i18n::msg('yduplicate_fields_view'));
$field->setNotice(rex_i18n::msg('yduplicate_comma_separated'));

$field = $form->addTextField('url', null, ['class' => 'form-control']);
$field->setLabel(rex_i18n::msg('yduplicate_url'));
$field->setNotice(rex_i18n::msg('yduplicate_url_slug'));

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('yduplicate_title'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');
