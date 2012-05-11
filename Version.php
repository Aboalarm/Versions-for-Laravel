<?php


class Version extends Eloquent {

	static $table = "versions";
	static $timestamps = false;
	
	public static function freeze($src) {
		$data = json_encode($src->original);
		$table = strtolower(get_class($src));
		$obj_id = $src->original['id'];
		return DB::table(Version::$table)->insert(array('data' => $data, 'object_table' => $table, 'object_id' => $obj_id));
	}
	
	public static function unfreeze($document_id) {
		$data = DB::table(Version::$table)->where_id($document_id)->first();
		$data->data = json_decode($data->data);
		return new $data->object_table($data->data);
	}
	
	public static function getFrozenObjects($object_id, $object_table) {
		return DB::table(Version::$table)->where('object_id', '=', $object_id)->where('object_table', '=', $object_table)->get();
	}
	
	public static function getLatestFreeze($object_id, $object_table) {
		return DB::table(Version::$table)->where('object_id', '=', $object_id)->where('object_table', '=', $object_table)->order_by('created_at', 'desc')->first();
	}

}

?>