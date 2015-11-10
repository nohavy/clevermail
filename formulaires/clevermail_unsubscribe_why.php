<?php

function formulaires_clevermail_unsubscribe_why_charger_dist() {
	if (isset($_GET['id']) && $_GET['id'] != '') {
		$action = sql_fetch(sql_select("*","spip_cm_pending", array("pnd_action_id=".sql_quote($_GET['id']), "pnd_action='unsubscribe'")));
    if(empty($action)) {
    	return array('message_erreur' => 'Votre demande ne peut pas être traitée', 'editable' => false);
    } else {
    	$valeurs = array('editable' => true);
    }

    $list = sql_fetsel("*", "spip_cm_lists", "lst_id = ".intval($action['lst_id']));
    $pos = strpos($list['lst_name'], "/");
    if (strpos($list['lst_name'], '/') === false) {
    	$lettre = supprimer_numero($list['lst_name']);
    	$categorie = '';
		  $list_name_complet = $lettre;
    } else {
    	$lettre = supprimer_numero(substr($list['lst_name'], strpos($list['lst_name'], '/') + 1));
    	$categorie = supprimer_numero(substr($list['lst_name'], 0, strpos($list['lst_name'], '/')));
	    $list_name_complet = $categorie." / ".$lettre;
    }

		$valeurs["list_name"] = $list_name_complet;
		return $valeurs;
	} else {
		return array('message_erreur' => 'Votre demande ne peut pas être traitée', 'editable' => false);
	}

}

function formulaires_clevermail_unsubscribe_why_verifier_dist() {
  $erreurs = array();
  if (!_request('choices_unsubscribe')) {
    $erreurs['choices_unsubscribe'] = _T('clevermail:choix_obligatoire');
  } elseif (in_array('5', _request('choices_unsubscribe'))) {
    if (!_request('autre')) {
      $erreurs['autre'] = 'Préciser les autres raisons.';
    }
  }
  
	if (count($erreurs)) {
    $erreurs['message_erreur'] = _T('clevermail:veuillez_corriger_votre_saisie');
  }

  return $erreurs;
}

function formulaires_clevermail_unsubscribe_why_traiter_dist() {
  if (isset($_GET['id']) && $_GET['id'] != '') {
    $action = sql_fetch(sql_select("*","spip_cm_pending", array("pnd_action_id=".sql_quote($_GET['id']), "pnd_action='unsubscribe'")));
    if(empty($action)) {
      return array('message_erreur' => 'Votre demande ne peut pas être traitée', 'editable' => false);
    }
    
    $list = sql_fetsel("*", "spip_cm_lists", "lst_id = ".intval($action['lst_id']));
    $pos = strpos($list['lst_name'], "/");
    if (strpos($list['lst_name'], '/') === false) {
      $lettre = supprimer_numero($list['lst_name']);
      $categorie = '';
      $list_name_complet = $lettre;
    } else {
      $lettre = supprimer_numero(substr($list['lst_name'], strpos($list['lst_name'], '/') + 1));
      $categorie = supprimer_numero(substr($list['lst_name'], 0, strpos($list['lst_name'], '/')));
      $list_name_complet = $categorie." / ".$lettre;
    }

    sql_delete("spip_cm_lists_subscribers", "lst_id = ".intval($action['lst_id'])." AND sub_id = ".intval($action['sub_id']));
    // remove posts from this list already queued
    sql_delete("spip_cm_posts_queued", "sub_id = ".intval($action['sub_id'])." AND pst_id IN (".implode(',', sql_fetsel("lst_id", "spip_cm_posts", "lst_id=".intval($action['lst_id']), "lst_id")).")");

    $lst_name = sql_getfetsel("lst_name", "spip_cm_lists", "lst_id = ".intval($action['lst_id']));
    $message_ok = _T('clevermail:desinscription_validee',array('lst_name' => $list_name_complet));
    
    // E-mail d'alerte envoye au moderateur de la liste
    $sub = sql_fetsel("*", "spip_cm_subscribers", "sub_id = ".intval($action['sub_id']));

    sql_insertq("spip_cm_unsubscribe_why", 
      array('sub_email' => $sub['sub_email'], 
        'sub_champ_exercice' => $sub['sub_champ_exercice'], 
        'sub_territoire' => $sub['sub_territoire'], 
        'date_unsubscribe' => date('Y-m-d H:i:s'),
        'choices_unsubscribe' => implode(':', $_POST['choices_unsubscribe']),
        'choices_autre' => $_POST['autre']
      )
    );
    
    $list = sql_fetsel("*", "spip_cm_lists", "lst_id = ".intval($action['lst_id']));
    $destinataire = $list['lst_moderator_email'];
    $sujet = '['.$list['lst_name'].'] Désinscription de '.addslashes($sub['sub_email']);
    $corps = _T('clevermail:mail_info_desinscription_corps', array('nom_site' => $GLOBALS['meta']['nom_site'], 'url_site' => $GLOBALS['meta']['adresse_site'], 'sub_email' => addslashes($sub['sub_email']), 'lst_name' => $list['lst_name']));
    $expediteur = sql_getfetsel("set_value", "spip_cm_settings", "set_name='CM_MAIL_FROM'");
    $envoyer_mail = charger_fonction('envoyer_mail', 'inc');
    $envoyer_mail($destinataire, $sujet, $corps, $expediteur);

    $abonne = sql_getfetsel("sub_email", "spip_cm_subscribers", "sub_id=".intval($action['sub_id']));
    $liste = sql_getfetsel("lst_name", "spip_cm_lists", "lst_id=".intval($action['lst_id']));
    if (sql_countsel("spip_cm_lists_subscribers", "sub_id=".intval($action['sub_id'])) == 0) {
      sql_updateq("spip_cm_subscribers",
        array('sub_email' => md5(substr($abonne,0,strpos($abonne, '@'))).substr($abonne,strpos($abonne, '@'))), "sub_id = ".intval($action['sub_id']));
    }
    spip_log('Suppression du l\'abonnement de « '.$abonne.' » de la liste « '.$liste.' » (id='.$action['lst_id'].')', 'clevermail');
    sql_delete("spip_cm_pending", "pnd_action_id=".sql_quote($_GET['id']));
    return array(
      'message_ok' => $message_ok,
      'redirect' => 'spip.php'
    );
  } else {
    return array('message_erreur' => 'Votre demande ne peut pas être traitée', 'editable' => false);
  }
}
?>
