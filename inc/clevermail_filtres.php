<?php
/**
 * Afficher un message "aucun truc"/"un truc"/"N trucs", inspire de singulier_ou_pluriel
 *
 * @param int $nb
 * @param string $chaine_aucun
 * @param string $chaine_un
 * @param string $chaine_plusieurs
 * @return string
 */
function aucun_ou_un_ou_plusieurs($nb, $chaine_aucun, $chaine_un, $chaine_plusieurs, $vars = array()) {
	$vars = array_merge($vars, array('nb' => intval($nb)));
  if (intval($nb) == 0) {
  	return _T($chaine_aucun, $vars);
  } elseif (intval($nb) == 1) {
  	return _T($chaine_un, $vars);
  } else {
    return _T($chaine_plusieurs, $vars);
  }
}

function cm_date($timestamp) {
  return date('d/m/Y', $timestamp);
}

function cm_heure($timestamp) {
  return date('H:i', $timestamp);
}

function unsubscribe_why($v){
  if($v) {
    $results = '<ul>';
    $choices = explode(':', $v);
    foreach ($choices as $value) {
      $choice_unsubscribe_nom = sql_getfetsel("nom", "spip_cm_choice_unsubscribe", "id = ".intval($value));
      $results .= '<li>' . $choice_unsubscribe_nom . '</li>';
    }
    $results .= '</ul>';
    return $results;
  }
  return '';
}

?>