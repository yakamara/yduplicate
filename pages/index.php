<?php

$subpage = rex_be_controller::getCurrentPagePart(2);

echo rex_view::title(rex_i18n::msg('yduplicate'));

rex_be_controller::includeCurrentPageSubPath();
