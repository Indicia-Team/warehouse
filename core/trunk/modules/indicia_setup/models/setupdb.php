<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * database setup model
 *
 * @package Indicia
 * @subpackage Model
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Armand Turpel <armand.turpel@gmail.com>
 * @version $Rev$ / $LastChangedDate$ / $Author$
 */

class Setupdb_Model extends Model
{
    /**
     * database connection object
     *
     * @var object $dbconn
     */
    private $dbconn = false;

    public function __construct(){}

    /**
     * connect to the database
     *
     * @return resource false on error
     */
    public function dbConnect($host, $port, $name, $user, $password)
    {
        $this->check($host, $port, $name, $user, $password);
        return $this->dbconn = pg_connect("host     = {$host}
                                           port     = {$port}
                                           dbname   = {$name}
                                           user     = {$user}
                                           password = {$password}");
    }

  /**
   * Checks that each of the mandatory parameters are populated. Throws an exception if not.
   */
    private function check($host, $port, $name, $user, $password) {
      if (!$host) {
        throw new Exception(Kohana::lang('setup.host_required'));
      }
      if (!$port) {
        throw new Exception(Kohana::lang('setup.port_required'));
      }
      if (!$name) {
        throw new Exception(Kohana::lang('setup.name_required'));
      }
      if (!$user) {
        throw new Exception(Kohana::lang('setup.user_required'));
      }
      if (!$password) {
        throw new Exception(Kohana::lang('setup.password_required'));
      }
    }

    /**
     * start transaction
     *
     */
    public function begin()
    {
        pg_query($this->dbconn, "BEGIN");
    }

    /**
     * end transaction
     *
     */
    public function commit()
    {
        pg_query($this->dbconn, "COMMIT");
    }

    /**
     * rollback transaction
     *
     */
    public function rollback()
    {
        pg_query($this->dbconn, "ROLLBACK");
    }

    /**
     * create schema
     *
     * @param string $schema
     * @return bool
     */
    public function createSchema( $schema )
    {
        // remove any existing schema with this name
        //
        if(false === pg_query($this->dbconn, "DROP SCHEMA IF EXISTS {$schema} CASCADE"))
        {
            return pg_last_error($this->dbconn);
        }

        // create schema
        //
        if(false === pg_query($this->dbconn, "CREATE SCHEMA {$schema}"))
        {
            return pg_last_error($this->dbconn);
        }

        // add schema to search path
        //
        if(true !== ($result = $this->set_search_path( $schema )))
        {
            return $result;
        }

        return true;
    }

    /**
     * set schema search path
     *
     * @param string $schema
     * @return bool
     */
    private function set_search_path( $schema )
    {
        //
        // if the schema dosent exists we get an error
        //
        if(false === pg_query($this->dbconn, "SET search_path TO {$schema}, public, pg_catalog"))
        {
            return pg_last_error($this->dbconn);
        }

        return true;
    }

    /**
     * check if postscript scripts are installed
     *
     * @return bool
     */
    public function checkPostgis()
    {
        if(false === ($result = pg_query($this->dbconn, "SELECT postgis_scripts_installed()")))
        {
            return pg_last_error($this->dbconn);
        }

        return true;
    }

    /**
     * insert values in the system table
     *
     * @return bool
     */
    public function insertSystemInfo()
    {
        $version = Kohana::config_load('version');        
        // Note the version is set to 0.1, since this is the initially installed db version which will immediately be updated.
        if (false === pg_query($this->dbconn, "INSERT INTO \"system\"
                                                    (\"version\", \"name\", \"repository\", \"release_date\")
                                               VALUES
                                                   ('0.1',
                                                    'Indicia',
                                                    '{$version['repository']}',
                                                    '{$version['release_date']}')"))
        {
            return pg_last_error($this->dbconn);
        }

        return true;
    }

    /**
     * check postgres serversion. at least 8.2 required
     *
     * @return mixed bool true if successful, false if unknown version, else server_version
     */
    public function check_postgres_version()
    {
        $server_version = pg_parameter_status( $this->dbconn, "server_version" );

        if(false !== $server_version)
        {
            if(-1 == version_compare($server_version, "8.2"))
            {
                return $server_version;
            }

            // version ok
            return true;
        }

        // unknown server_version
        return false;
    }

    /**
     * query
     *
     * @param string $content
     * @return mixed bool if successful else error string
     */
    public function query( $content )
    {
        try {
          pg_query($this->dbconn, $content);
        }
        catch (Exception $e){
            return pg_last_error($this->dbconn);
        }

        return true;
    }

    /**
     * grant privileges to additional users
     *
     * @param string $users comma separated if more than one
     * @param string $schema schema name
     * @return bool
     */
    public function grant( $users, $schema )
    {
        // assign users in array
        $_users = explode(",", $users);

        // grant on schema
        //
        foreach($_users as $user)
        {
            $user = trim($user);
            if(false === pg_query($this->dbconn, "GRANT ALL ON SCHEMA \"{$schema}\" TO \"{$user}\"" ))
            {
                return pg_last_error($this->dbconn);
            }
        }

        // grant on tables
        //
        if(false !== ($result = pg_query($this->dbconn, "SELECT table_name FROM information_schema.tables WHERE table_schema = '{$this->table_schema}'")))
        {
            while ($row = pg_fetch_row($result))
            {
                foreach($_users as $user)
                {
                    $user = trim($user);
                    if(false === pg_query($this->dbconn, "GRANT ALL ON TABLE \"{$row[0]}\" TO \"{$user}\"" ))
                    {
                        return pg_last_error($this->dbconn);
                    }
                }
            }
        }
        else
        {
            return pg_last_error($this->dbconn);
        }

        // grant on views
        //
        if(false !== ($result = pg_query($this->dbconn, "SELECT table_name FROM information_schema.views WHERE table_schema = '{$this->table_schema}'")))
        {
            while ($row = pg_fetch_row($result))
            {
                foreach($_users as $user)
                {
                    $user = trim($user);
                    if(false === pg_query($this->dbconn, "GRANT ALL ON TABLE \"{$row[0]}\" TO \"{$user}\"" ))
                    {
                        return pg_last_error($this->dbconn);
                    }
                }
            }
        }
        else
        {
            return pg_last_error($this->dbconn);
        }

        // grant on sequences
        //
        if(false !== ($result = pg_query($this->dbconn, "SELECT sequence_name FROM information_schema.sequences")))
        {
            while ($row = pg_fetch_row($result))
            {
                foreach($_users as $user)
                {
                    $user = trim($user);
                    if(false === pg_query($this->dbconn, "GRANT ALL ON SEQUENCE \"{$row[0]}\" TO \"{$user}\"" ))
                    {
                        return pg_last_error($this->dbconn);
                    }
                }
            }
        }
        else
        {
            return pg_last_error($this->dbconn);
        }

        return true;
    }

}

?>
