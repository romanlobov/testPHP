<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Check main page');
$I->amOnUrl('https://yandex.ru/');
$I->see("Яндекс");
?>