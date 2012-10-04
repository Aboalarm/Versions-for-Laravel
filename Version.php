<?php


/**
 * @category    Bundle
 * @package     Version
 * @license 	MIT License <http://www.opensource.org/licenses/mit>
 * 
 * @see  https://github.com/Aboalarm/Versions-for-Laravel
 */




class Version {


	static $table = "versions";


	/**
	* Get the table name, where we will save the versions
	* from the Laravel config
	*
	* @return string
	*/
	public static function getVersionsTable() {
		return Config::get('version.table',Config::get('Version::version.table',static::$table));
	}

	
	/**
	* Save a version of the current state of an object. 
	* Will automatically check for duplicates and won't save them. 
	* You can specify a name for the version if you want to. 
	*
	* @param string $src
	* @param string $name
	* @return bool
	*/
	public static function add($src, $name = '') {
		$data 	= json_encode($src->original);
		$table 	= strtolower(get_class($src));
		$obj_id = isset($src->original['id']) ? $src->original['id'] : false;

		if($obj_id)
		{
			$creation_date = date('Y-m-d H:i:s');

			try
			{
				return DB::table(Version::getVersionsTable())
				->insert(array(
					'data' => $data, 
					'object_table' => $table, 
					'object_id' => $obj_id, 
					'name' => $name, 
					'hash' => md5($data),
					'created_at' => $creation_date, 
					'updated_at' => $creation_date, 
					));	
				
			}
			catch (Exception $e)
			{
				return false;
			}
			
		} 

		return false;
	}



    /**
     * Save a version of the object only if the object has already been saved to DB and 
     * it has has been modified from original.
     * Version::add() will prevent duplicates via a unique index in the DB. This method
     * relies on the Model::$exists and Model::dirty().  Therefore, this method can be 
     * called safely without an unnecessary hit on the DB.
     * 
     * @param Model $obj
     * @return bool
     */
    public static function addIfChanged($obj)
    {
        if($obj->exists AND $obj->dirty()) return static::add($obj);
        else return false;
    }


	
	/**
	* Loads a specific saved version by its primary key
	*
	* @param int $version_id
	* @return void
	*/
	public static function load($version_id) {
		$data = DB::table(Version::getVersionsTable())->where_id($version_id)->first();
		$data->data = json_decode($data->data);
		return new $data->object_table($data->data);
	}
	


	/**
	* Get all versions of an object as an array of stdClass objects
	* 
	* @param Model $obj
	* @return array
	*/
	public static function all($obj) {
		$objs = static::query($obj)->order_by('updated_at', 'desc')->get();
		array_walk($objs, function(&$val){
		    $val->object = json_decode($val->data);
		});
		return $objs;
	}



	/**
	* How many versions are saved for a given object? 
	*
	* @param Model $obj
	* @return int
	*/
	public static function count($obj) {
		return static::query($obj)->count();
	}


	
	/**
	* Retrieve the most recent version of an object
	*
	* @param Model $obj
	* @return object
	*/
	public static function latest($obj) {
		$obj = static::query($obj)->order_by('created_at', 'desc')->first();
		$obj->object = json_decode($obj->data);
		return $obj;
	}



	/**
	* Delete a version of an object
	*
	* @param int $version_id
	* @return bool
	*/
	public static function delete($version_id) {
		return DB::table(Version::getVersionsTable())->delete($version_id);
	}



	/**
	* Delete all versions of an object
	*
	* @param Model $obj
	* @return bool
	*/
	
	public static function deleteAll($obj) {
        return static::query($obj)->delete();
	}



    /**
     * Build base query for versions of the supplied object.
     * 
     * @param $obj
     * @return Laravel\Database\Query
     */
    protected static function query($obj) {
        return DB::table(Version::getVersionsTable())->where('object_id', '=', $obj->attributes['id'])->where('object_table', '=', strtolower(get_class($obj)));
	}

}

?>