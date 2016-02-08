<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

defined('BOOTSTRAP') or die('Access denied');

if ($mode == 'generate_ammo_file') {
    $ammo_file_generator = new \Tygh\Addons\YandexTank\AmmoFileGenerator(
        Tygh::$app['db'],
        array_keys(\Tygh\Languages\Languages::getActive()),
        \Tygh\Registry::get('config.current_host')
    );

    $ammo_file_generator->generate(
        \Tygh\Registry::get('config.dir.var') . 'ammo.txt'
    );
}