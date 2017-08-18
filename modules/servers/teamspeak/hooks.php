<?php

require_once __DIR__ . "/lib/Requests.php";
require_once __DIR__ . "/lib/TSDNS.php";

use Illuminate\Database\Capsule\Manager as Capsule;

function hook_check_subdomain(array $params)
{
    try {
        $settings = Capsule::table('modwhmcs_teamspeak_settings')->select('enabletsdns', 'domaintsdns', 'urlapi', 'keyapi')->first();

        if ($settings->enabletsdns) {

            $tsdnsClient = new TSDNS($settings->urlapi, $settings->keyapi);

            if (!$tsdnsClient) {
                throw new Exception('Não foi possível verificar a disponibilidade do subdomínio. Entre em contato com o suporte.');
            }

            $id = Capsule::table('tblcustomfields')->where('fieldname', 'Subdomínio')->value('id');

            $request = $tsdnsClient->getZone($params['customfield'][$id] . '.' . $settings->domaintsdns);

            $zone = json_decode($request->body);

            if (count($zone->message)) {
                throw new Exception('Subdomínio já está sendo utilizado');
            }
        }
    } catch (Exception $e) {
        $errors = array();
        $errors[] = $e->getMessage();
        return $errors;
    }
}

add_hook("ShoppingCartValidateProductUpdate", 1, "hook_check_subdomain");