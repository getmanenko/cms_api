<?php
use samson\activerecord\dbQuery;

/**
 * CMS(Content management system) - Получить Объект для работы с "Системой управления содержим" сайта
 * @return CMS Экземпляр объекта для работы с SamsonCMS
 * @deprecated
 */
function & cms(){ static $_v; return ( $_v = isset($_v) ? $_v : m('cmsapi'));}

/**
 * CMS Material( Материал SamsonCMS ) - Получить материал SamsonCMS по идентификатору
 * @see iCMS::material
 * @param mixed $selector 	Селектор для отбора материала SamsonCMS
 * @param mixed $field 		Поле материала SamsonCMS в котором ищеться переданный селектор
 * @return CMSMaterial Материал SamsonCMS
 * @deprecated
 */
function & cmsmat( $selector, $field = 'Url' ){ static $_c; $_c = isset($_c) ? $_c : cms(); return $_c->material( $selector, $field ); }

/**
 * CMS Navigation( Элемент навигации SamsonCMS ) - Получить элемент навигации SamsonCMS по идентификатору
 * @see iCMS::navigation
 * @param mixed $selector 	Селектор для элемента навигации SamsonCMS
 * @param mixed $field 		Поле элемента навигации SamsonCMS в котором ищеться переданный селектор
 * @return CMSNav Элемент навигации SamsonCMS
 * @deprecated
 */
function & cmsnav( $selector, $field = 'Url' ){static $_c; $_c = isset($_c) ? $_c : cms(); return $_c->navigation( $selector, $field );}

/**
 * 
 * @param unknown $selector
 * @param unknown $field
 * @deprecated
 */
function & cmsnavmaterials( $selector, $handler = null, $field = 'Url' )
{ 
	// Static pointer to cms module
	static $_c; $_c = isset($_c) ? $_c : cms(); 
	return $_c->navmaterials( $selector, $field, handler ); 
}

/**
 * Получить элемент навигации сайта
 * @see iCMS::navigation
 * @param string 	$selector	Значение для поиска элемента навигации сайта
 * @param iCMSNav 	$cmsnav		Переменная в коротую будет возвращен найденный ЭНС
 * @param string 	$field		Имя поля по которому выполняется поиск
 * @return boolean Найден ли ЭНС или нет
 * @deprecated
 */
function ifcmsnav( $selector, & $cmsnav = NULL, $field = 'Url' )
{
	// Статически сохраним указатель на объект CMS
	static $_c; $_c = isset($_c) ? $_c : cms();
	
	// Попытаемся получить указатель на ЭНС
	$cmsnav = $_c->navigation( $selector, $field );
	
	// If we did not get cmsmaterial
	if( $cmsnav === null ) return false;	

	// Everything is ok
	return true;
}

/**
 * Получить материал
 * @see CMS::material
 * @param string 		$selector	Значение для поиска материала
 * @param CMSMaterial 	$cmsmat		Переменная в коротую будет возвращен найденный материал
 * @param string 		$field		Имя поля по которому выполняется поиск
 * @return boolean Найден ли материал или нет
 * @deprecated
 */
function ifcmsmat( $selector, & $cmsmat = NULL, $field = 'Url' )
{
	// Get CMS reference and make it static
	static $_c; $_c = isset($_c) ? $_c : cms();

	// Get material
	$cmsmat = $_c->material( $selector, $field );
	
	// If we did not get cmsmaterial
	if( $cmsmat === null ) return false;	

	// Everything is ok
	return true;
}

/** @return \samson\cms\Query
 * @deprecated
 */
function cmsquery(){ return new samson\cms\CMSMaterialQuery(); }

/** @deprecated @return dbQuery
 * @deprecated
 */
function _cmsmaterial(){ return new dbQuery('samson\cms\CMSMaterial'); }

/** @deprecated @return dbQuery
 * @deprecated
 */
function _cmsmaterialfield(){	return new dbQuery('samson\cms\CMSMaterialField'); }

/** @deprecated @return dbQuery
 * @deprecated
 */
function _cmsnav(){ return new dbQuery('\samson\cms\CMSNav'); }

/** @deprecated @return dbQuery
 * @deprecated
 */
function _cmsnavfield(){	return new dbQuery('samson\cms\CMSNavField');}

/** @deprecated @return dbQuery
 * @deprecated
 */
function _cmsnavmaterial(){return new dbQuery('samson\cms\CMSNavMaterial');}
