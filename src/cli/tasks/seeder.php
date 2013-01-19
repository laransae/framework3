<?php namespace Laravel\CLI\Tasks;

use Laravel\Database;
use Laravel\Bundle;
use Laravel\Str;

class Seeder extends Task {

    /**
     * The database seed file list.
     *
     * @var array
     */
    protected $seedFiles;

    /**
     * Seed the database from the given path.
     *
     * @param array   $arguments
     * @return int
     */
    public function seed( $arguments = array() ) {
        $total = 0;

        foreach ( $this->globSeedFiles( ) as $file ) {
            $records = $this->loadSeedFile( $file );

            // We'll grab the table name here, which could either come from the array or
            // from the filename itself. Then, we will simply insert the records into
            // the databases.
            $table = $this->fetchTableName( $records, $file );

            Database::table( $table )->delete();

            Database::table( $table )->insert( $records );

            $total += $count = count( $records );
            echo sprintf( "Seeded: `%s` (%d rows)\n", $table, $count );
        }

        return $total;
    }


    /**
     * Get all of the files at a given path.
     *
     * @param string  $path
     * @return array
     */
    protected function globSeedFiles( ) {
        if ( isset( $this->seedFiles ) ) return $this->seedFiles;

        // If the seeds haven't been read before, we will glob the directories and sort
        // them alphabetically just in case the developer is using numbers to make
        // the seed run in a certain order based on their database design needs.

        $folders = array( path( 'app' ) . 'seeds' . DS );
        foreach ( Bundle::$bundles as $bundle ) {
            $folders[] = Bundle::path( $bundle['location'] ) . 'seeds' . DS;
        }

        $files = array();
        foreach ( $folders as $folder ) {
            $files = array_merge( $files, glob( $folder . '*.php' ) );
        }

        if ( false !== $this->getParameter( 'except' ) ) {
            $exclude = explode( ',', $this->getParameter( 'except' ) );
            foreach ( $files as $key => $file ) {
                if ( in_array( pathinfo( $file, PATHINFO_FILENAME ), $exclude ) ) {
                    unset( $files[$key] );
                }
            }
        }

        sort( $files );

        return $this->seedFiles = $files;
    }

    /**
     * Get the table name from the given records and/or file.
     *
     * @param array   $records
     * @param string  $file
     * @return string
     */
    protected function fetchTableName( & $records, $file ) {
        $table = array_get( $records, 'table', basename( $file, '.php' ) );

        unset( $records['table'] );

        return $table;
    }

    /**
     * Get the returned data definition array from a seed file.
     *
     * @param string  $path
     * @return mixed
     */
    protected function loadSeedFile( $path ) {
        if ( file_exists( $path ) ) return require $path;

        throw new FileNotFoundException( "File does not exist at path {$path}" );
    }

    protected function getParameter( $key ) {
        if ( isset( $_SERVER['CLI'][Str::upper( $key )] ) ) {
            return ( $_SERVER['CLI'][Str::upper( $key )] == '' ) ? true : $_SERVER['CLI'][Str::upper( $key )];
        }
        else {
            return false;
        }
    }
}
