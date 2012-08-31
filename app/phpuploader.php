<?php
//----------------------------------------------
//    partitioned upload file handler script
//----------------------------------------------

//
//    specify upload directory - storage 
//    for reconstructed uploaded files

$upload_dir ="uploaded/";

//
//    specify stage directory - temporary storage 
//    for uploaded partitions
$stage_dir = "uploaded/stage/";

//
//    retrieve request parameters
$file_param_name = 'file';
$file_name = $_FILES[ $file_param_name ][ 'name' ];
$file_id = $_POST[ 'fileId' ];
$partition_index = $_POST[ 'partitionIndex' ];
$partition_count = $_POST[ 'partitionCount' ];
$file_length = $_POST[ 'fileLength' ];



//
//    the $client_id is an essential variable, 
//    this is used to generate uploaded partitions file prefix, 
//    because we can not rely on 'fileId' uniqueness in a 
//    concurrent environment - 2 different clients (applets) 
//    may submit duplicate fileId. thus, this is responsibility 
//    of a server to distribute unique clientId values
//    (or other variable, for example this could be session id) 
//    for instantiated applets.
$client_id = $_GET[ 'clientId' ];

//
//    move uploaded partition to the staging folder 
//    using following name pattern:
//    ${clientId}.${fileId}.${partitionIndex}
$source_file_path = $_FILES[ $file_param_name ][ 'tmp_name' ];
$target_file_path = $stage_dir . $client_id . "" . $file_id . 
    "" . $partition_index;
	

  
if( !move_uploaded_file( $source_file_path, $target_file_path ) ) {
    echo "Error:Can't move uploaded file";
    return;
}



$all_in_place = true;
$partitions_length = 0;
for( $i = 0; $all_in_place && $i < $partition_count; $i++ ) {
    $partition_file = $stage_dir . $client_id . "" . $file_id . "" . $i;
    if( file_exists( $partition_file ) ) {
        $partitions_length += filesize( $partition_file );
    } else {
        $all_in_place = false;
    }
}

//
//    issue error if last partition uploaded, but partitions validation failed
if( $partition_index == $partition_count - 1 &&
        ( !$all_in_place || $partitions_length != intval( $file_length ) ) ) {


	unlink($target_file_path);

	header('HTTP/1.1 500 Internal Server Error');
    echo "Error:Upload validation error";
    return;
}

//
//    reconstruct original file if all ok
if( $all_in_place ) {
    $file = $upload_dir . $client_id . "" . $file_id;
    $file_handle = fopen( $file, 'w' );
    for( $i = 0; $all_in_place && $i < $partition_count; $i++ ) {
        //
        //    read partition file
        $partition_file = $stage_dir . $client_id . "" . $file_id . "" . $i;
        $partition_file_handle = fopen( $partition_file, "rb" );
        $contents = fread( $partition_file_handle, filesize( $partition_file ) );
        fclose( $partition_file_handle );
        //
        //    write to reconstruct file
        fwrite( $file_handle, $contents );
        //
        //    remove partition file
        unlink( $partition_file );
    }
    fclose( $file_handle );

    
    // rename file based on hash.
    $split = explode('.', $file_name);
    $hash =  hash_file('md5', $file);
    $filename = $upload_dir . $hash . '.' . $split[1];
    rename($file,$filename);


$size = getimagesize($filename);

	if($size[0]!=150 || $size[1]!=100){
		if($size[1]>225){
			unlink($filename);
			header('HTTP/1.1 500 Internal Server Error');
			return;

		}
	}
}

?>