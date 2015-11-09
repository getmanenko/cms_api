<?php 
namespace samson\cms;

use samson\activerecord\Condition;
use samson\core\iModuleViewable;
use samson\activerecord\dbRecord;

/**
 * Class for managing CMS material with all related data
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 0.9.1
 */
class CMSMaterial extends Material implements iModuleViewable
{
    public $class_name = 'material';

    // TODO: Sorting must use priority field
    /** Gallery images sorter */
    public static function usortGallery($a, $b)
    {
        return $a->PhotoID > $b->PhotoID;
    }

    /**
	 * Universal method for retrieving material from database with all additional data
	 * such as additional field data with ability to sort, limit, filter by it and gallery data/
	 * 
	 * @param array $field_value 	Array( FIELD_NAME, FIELD_VALUE ) for filtering
	 * @param string $db_cmsnav		Pointer to CMSNav for getting particular materials
	 * @param string $draft			Request "draft" filter
	 * @param string $published		Request "published" filter
	 * @param string $order_by		Request sorting order
	 * @param array $limit			Request limit params
	 * @param string $group_by		Request group by params
	 * @param mixed $handler		External query handler
	 * @param mixed $handler_params	External query handler additional parameters
     * @param array $class_name     ss
	 * @return array CMSMaterial collection by specified request parameters
     * @deprecated New method is coming soon :)
	 */
    public static function & get(
        array $field_value = null,
        $db_cmsnav = null,
        $draft = null,
        $published = null,
        $order_by = null,
        $limit = null,
        $group_by = null,
        $handler = null,
        $handler_params = array(),
        $class_name = null
    ) {
        $db_materials = array();
        if (!isset($class_name)) {
            $class_name = 'samson\cms\CMSMaterial';
        }
        // Create db request
        $query = dbQuery($class_name)
        ->cond('Active', 1)
        //->cond('locale', locale())
        ->join('samson\cms\CMSGallery')
        ->join('user')
        ->join('samson\cms\CMSNavMaterial')
        //->own_group_by('material.MaterialID')
        ;

        // If we need limiting results
        if (isset($limit)) {
            $query->limit($limit[0], $limit[1]);
        }

        // If we need grouping results
        if (isset($group_by)) {
            $query->group_by($group_by);
        }

        // If request field/value passed
        if (isset($field_value)) {
            $query->cond($field_value[0], $field_value[1]);
        }

        // If we have condifition for drafts
        if (isset($draft)) {
            $query->cond('Draft', $draft);
        }

        // If we have condition for published
        if (isset($published)) {
            $query->cond('Published', $published);
        }

        // If we have ordering condition
        if (is_array($order_by)) {
            $query->order_by($order_by[0], $order_by[1]);

        // Otherwise order by id
        } elseif (!isset($order_by)) {
            $query->order_by('MaterialID', 'DESC');
        }

        // If we have CMSNav filter
        if (isset($db_cmsnav)) {
            $navId = 0;
            if (!is_array($db_cmsnav)) {
                $navId = $db_cmsnav->id;
            } else {
                $navId = $db_cmsnav;
            }
            $query
                ->cond('structurematerial_StructureID', $navId)
                ->cond('structurematerial_Active', 1);
        }

        // if we have handler
        if (is_callable($handler)) {
            // Make first parameter original query object
            array_unshift($handler_params, $query);

            // Make query copy to return for different purposes
            call_user_func_array($handler, $handler_params);
        }
        //$GLOBALS['show_sql'] = true;
        // Perform db request
        if ($query->exec($db_materials)) {
            foreach ($db_materials as & $db_material) {
                //unset($GLOBALS['show_sql']);
                // Save instance to cache by URL
                dbRecord::$instances[ 'samson\cms\CMSMaterial' ][ $db_material->Url ] = $db_material;

                // Pointer to user data
                $db_material->user = $db_material->onetoone['_user'];

                // Fill gallery data
                $db_material->gallery = array();
                if (isset($db_material->onetomany['_gallery'])) {
                    // Sort gallery images
                    usort($db_material->onetomany['_gallery'], '\samson\cms\CMSMaterial::usortGallery');

                    foreach ($db_material->onetomany['_gallery'] as $db_gallery) {
                        $db_material->gallery[] = $db_gallery->Path.$db_gallery->Src;
                    }
                }

                if (isset($db_material->onetomany['_structurematerial'])) {
                    $db_material->structure = $db_material->onetomany['_structurematerial'];
                }

                // Remove relation collections
                $db_material->onetoone = array();
                $db_material->onetomany = array();
            }
        }

        return $db_materials;
    }




    /** Collection of images for material */
    public $gallery;

    /** User who own this material */
    public $user;

    /** @see \samson\core\iModuleViewable::toView() */
    public function toView($key_prefix = '', array $restricted = array())
    {
        // Created restricred cmsmaterial fields collection
        $restricted = array_merge(self::$restricted, $restricted, array('fields'));

        // Default behavior
        $values = parent::toView($key_prefix, $restricted);

        // If editors mode enabled
        if (isset($_SESSION['__CMS_EDITOR__'])) {
            // Iterate throught cmsmaterial fields and all additional fields
            foreach (get_object_vars($this) as $var => $value) {
                // If field not restricted - add to view data collection
                if (!in_array($var, $restricted) && !is_array($value) && !is_object($value)) {
                    $values['__dm__'.$key_prefix.$var] = $this->value($var, true);
                }
            }
        }

        return $values;
    }

	/** Change default dbRecord::save() logic with saving additional field data */
	public function save()
	{
		// Base logic
		parent::save();
		
		// Perform request for additional field metadata
		/*if( dbQuery('materialfield')->join('field')->exec( $db_fields ))
		{
			// Iterate fields and if field metadata has been found
			foreach ( $db_fields as & $db_field ) if( isset( $db_field->onetoone['_field'] ) )
			{
				// Save field value to db					
				$db_field->Value = $this[ $db_field->onetoone['_field']->Name ];			
				$db_field->save();
			}			
		}*/			
		// TODO: Add logic for gallery saving
	}
	
	/** Function for calling unexisting methods */
	public function __call( $name, $arguments )
	{		
		// If no arguments specified consider it is an array
		if( !isset($arguments[0]) ) $arguments = array( false );

		return $this->value( $name, $arguments[0]);		
	}
	
	/**
	 * Universal function for retrieving CMSMaterial field value
	 * but with support of inline editing function 
	 * 
	 * @param string 	$name 			Field name
	 * @param boolean 	$returnValue 	Return or echo value
	 * @return String If $returnValue is true thant returns value
	 */
	public function value( $name, $returnValue = false )
	{
		// If CMSMaterial has such field
		if( isset( $this[ $name ] ) || $this->$name )
		{					
			// Get field value
			$html = $this->$name;
				
			// If we are in editor mode
			if( isset($_SESSION['__CMS_EDITOR__']) )
			{				
				// Render editor value view
				$html = m('cmsapi')
                    ->view('app/view/editor/material.php')
				->set('field',$name)
				->set('id',$this->id)
				->set('value',$this->$name)
				->set('entity','cmsmaterial')
				->output();
			}
			
			// Echo or return
			if( $returnValue === false ) echo $html; 
			else return $html;
		}
	}
	
	/**
	 * Get array of CMSNavigation object for this material
	 * @return array Collection of CMSNav objects
	 */
	public function cmsnavs()
	{	
		// Perfrom DB request to get all connected cmsnavs
		$cmsnavs = array();
		if(dbQuery('samson\cms\CMSNavMaterial')->MaterialID($this->id)->exec($db_nms))
		{
			// Gather CMSNavs object to array
			foreach ($db_nms as $db_nm) $cmsnavs[] = cms()->navigation( $db_nm->StructureID, 'id' );
		}	
		
		return $cmsnavs;
	}
	
	/**
	 * Find all related material to current 
	 * @param function $handler External DB request handler
	 * @return array Collection of related materials
	 */
	public function & related( $handler = null )
	{		
		$db_materials = array();	
		
		//$GLOBALS['show_sql'] = true;	

		// Create DB query 
		$q = dbQuery('samson\cms\cmsrelatedmaterial');
		
		// If external query handler is passed
		if( isset($handler)) $q->handler($handler);
		
		// If we have found related materials
		if( $q->first_material( $this->id )->fields( 'second_material', $ids ) ) 
		{			
			// Get related CMSMaterials by ids 
			$db_materials = cmsquery()->MaterialID($ids)->published();
		}
		
		return $db_materials;
	}
	
	/**
	 * Find all materials that current material relates to
	 * @param function $handler External DB request handler
	 * @return \samson\cms\CMSMaterial[] Collection of materials that current material relates to
	 */
	public function & relates( $handler = null  )
	{
		$db_materials = array();
	
		//$GLOBALS['show_sql'] = true;		
		
		// Create DB query 
		$q = dbQuery('samson\cms\cmsrelatedmaterial');
		
		// If external query handler is passed
		if( isset($handler)) $q->handler($handler);
		
		// If we have found related materials
		if( $q->second_material( $this->id )->fields( 'first_material', $ids ) )
		{
			// Get related CMSMaterials by ids
			$db_materials = array_merge( $db_materials, cmsquery()->MaterialID($ids)->published());
		}		
	
		return $db_materials;
	}

    /**
     * Function to delete CMSMaterial completely with it's materialfield records
     */
    public function deleteWithRelations()
    {
        /** @var array $fields Array of materilfields of this material */
        $fields = null;
        /** @var int $count Variable to store count of materialfields */
        $count = 0;
        /** @var string $queryString Query to delete all materialfields */
        $queryString = 'DELETE FROM `'.\samson\activerecord\dbMySQLConnector::$prefix.'materialfield` WHERE';
        /** @var int $materialId Current material identifier */
        $materialId = $this->MaterialID;

        $this->delete();

        if (dbQuery('materialfield')->cond('MaterialID', $materialId)->exec($fields)) {
            /** @var \samson\activerecord\materialfield $field Variable to store materailfield object */
            foreach ($fields as $field) {
                $count++;
                if ($count >= count($fields)) {
                    $queryString .= ' `MaterialFieldID`='.$field->MaterialFieldID;
                } else {
                    $queryString .= ' `MaterialFieldID`='.$field->MaterialFieldID.' OR';
                }
            }
            db()->simple_query($queryString);
        }

        $queryString = 'DELETE FROM `'.\samson\activerecord\dbMySQLConnector::$prefix.'structurematerial` WHERE';
        $count = 0;
        /** @var array $structures Array of structurematerials of this material */
        $structures = null;
        if (dbQuery('structurematerial')->cond('MaterialID', $materialId)->exec($structures)) {
            /** @var \samson\activerecord\structurematerial $structure Variable to store structurematerial object */
            foreach ($structures as $structure) {
                $count++;
                if ($count >= count($structures)) {
                    $queryString .= ' `StructureMaterialID`=' . $structure->StructureMaterialID;
                } else {
                    $queryString .= ' `StructureMaterialID`=' . $structure->StructureMaterialID . ' OR';
                }
            }
            db()->simple_query($queryString);
        }
    }
    
    /**
     * Function to retrieve this material table by specified field
     * @param string $tableSelector Selector to identify table structure
     * @param string $field Database field by which search is performed
     * @param array $tableColumns Columns names list
     * @param string $externalHandler External handler to perform some extra code
     * @param array $params External handler params
     * @return array Collection of collections of table cells, represented as materialfield objects
     */
    public function getTable($tableSelector, $selector = 'StructureID', &$tableColumns = null, $externalHandler = null, $params = array())
    {
        /** @var array $resultTable Collection of collections of field cells */
        $resultTable = array();
        /** @var array $dbTableFieldsIds Array of table structure column identifiers */
        $dbTableFieldsIds = array();

        // Get structure object if we need to search it by other fields
        if ($selector != 'StructureID') {
            $structure = dbQuery('structure')->cond($selector, $tableSelector)->first();
            $tableSelector = $structure->id;
        }

        /** If this table has columns */
        if (dbQuery('structurefield')
            ->cond("StructureID", $tableSelector)
            ->fields('FieldID', $dbTableFieldsIds)
        ) {
            // Get localized and not localized fields
            $localizedFields = array();
            $unlocalizedFields = array();
            /** @var \samson\cms\CMSField $dbTableField Table column */
            foreach (dbQuery('field')->order_by('priority')->cond('FieldID', $dbTableFieldsIds)->exec() as $field) {
                /** Add table columns names */
                $tableColumns[] = $field->Name;
                if ($field->local == 1) {
                    $localizedFields[] = $field->id;
                } else {
                    $unlocalizedFields[] = $field->id;
                }
            }

            // Query to get table rows(table materials)
            $tableQuery = dbQuery('material')
                ->cond('parent_id', $this->MaterialID)
                ->cond('Active', '1')
                ->join('structurematerial')
                ->cond('structurematerial_StructureID', $tableSelector)
                ->order_by('priority')
                ;

            // Call user function if exists
            if (is_callable($externalHandler)) {
                // Give it query as parameter
                call_user_func_array($externalHandler, array_merge(array(&$tableQuery), $params));
            }

            // Get table row materials
            $tableMaterialIds = array();
            if ($tableQuery->fields('MaterialID', $tableMaterialIds)) {
                // Create field condition
                $localizationFieldCond = new Condition('or');

                // Create localized condition
                if (sizeof($localizedFields)) {
                    $localizedFieldCond = new Condition('and');
                    $localizedFieldCond->add('materialfield_FieldID', $localizedFields)
                        ->add('materialfield_locale', locale());
                    // Add this condition to condition group
                    $localizationFieldCond->add($localizedFieldCond);
                }

                // Create not localized condition
                if (sizeof($unlocalizedFields)) {
                    $localizationFieldCond->add('materialfield_FieldID', $unlocalizedFields);
                }

                // Create db query
                $materialFieldQuery = dbQuery('materialfield')
                    ->cond('MaterialID', $tableMaterialIds)
                    ->cond($localizationFieldCond)
                ;

                // Flip field identifiers as keys
                $tableColumnIds = array_flip($dbTableFieldsIds);
                $resultTable = array_flip ($tableMaterialIds);

                /** @var \samson\activerecord\material $dbTableRow Material object (table row) */
                foreach ($materialFieldQuery->exec() as $mf) {
                    if (!is_array($resultTable[$mf['MaterialID']])) {
                        $resultTable[$mf['MaterialID']] = array();
                    }

                    $resultTable[$mf['MaterialID']][$tableColumnIds[$mf->FieldID]] =
                        !empty($mf->Value) ? $mf->Value : (!empty($mf->numeric_value) ? $mf->numeric_value : $mf->key_value);
                }
            }
        }

        return array_values($resultTable);
    }
}
