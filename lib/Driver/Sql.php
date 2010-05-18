<?php
/**
 * Pastie storage implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:
 * <pre>
 * 'phptype' - The database type (e.g. 'pgsql', 'mysql', etc.).
 * 'table' - The name of the foo table in 'database'.
 * 'charset' - The database's internal charset.
 * </pre>
 *
 * Required by some database implementations:
 * <pre>
 * 'database' - The name of the database.
 * 'hostspec' - The hostname of the database server.
 * 'protocol' - The communication protocol ('tcp', 'unix', etc.).
 * 'username' - The username with which to connect to the database.
 * 'password' - The password associated with 'username'.
 * 'options' - Additional options to pass to the database.
 * 'tty' - The TTY on which to connect to the database.
 * 'port' - The port on which to connect to the database.
 * </pre>
 *
 * The table structure can be created by the scripts/sql/pastie_foo.sql
 * script.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.fsf.org/copyleft/bsd.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Pastie
 */
class Pastie_Driver_Sql extends Pastie_Driver
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Constructs a new SQL storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    public function savePaste($bin, $paste, $syntax = 'none', $title = '')
    {
        $this->_connect();
        
        $id = $this->_db->nextId('mySequence');
        if (PEAR::isError($id)) {
            throw new Horde_Exception_Prior($id);
        }

        $uuid = new Horde_Support_Uuid();

        $bin = 'default'; // FIXME: Allow bins to be Horde_Shares

        $query = 'INSERT INTO pastie_pastes (paste_id, paste_uuid, ' .
                 'paste_bin, paste_title, paste_syntax, paste_content, ' .
                 'paste_owner, paste_timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $values = array(
                      $id,
                      $uuid,
                      $bin,
                      $title,
                      $syntax,
                      $paste,
                      Horde_Auth::getAuth(),
                      time()
        );

        Horde::logMessage(sprintf('Pastie_Driver_Sql#savePaste(): %s', $query), 'DEBUG');
        Horde::logMessage(print_r($values, true), 'DEBUG');

        $result = $this->_write_db->query($query, $values);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'err');
            throw new Horde_Exception_Prior($result);
        }

        return $uuid;
    }

    /**
     * Retrieves the paste from the database.
     * 
     * @param array $params  Array of selectors to find the paste.
     *
     * @return array  Array of paste information
     */
    public function getPaste($params)
    {
        // Right now we will accept 'id' or 'uuid'
        if (!isset($params['id']) && !isset($params['uuid'])) {
            Horde::logMessage('Error: must specify some kind of unique id.', 'err');
            throw new Pastie_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        $query = 'SELECT paste_id, paste_uuid, paste_title, paste_syntax, '.
                 'paste_content, paste_owner, paste_timestamp ' .
                 'FROM pastie_pastes ';
        $values = array();
        if (isset($params['id'])) {
            $query .= 'WHERE paste_id = ? ';
            $values[] = $params['id'];
        } elseif (isset($params['uuid'])) {
            $query .= 'WHERE paste_uuid = ? ';
            $values[] = $params['uuid'];
        }

        $query .= 'AND paste_bin = ?';
        $values[] = 'default'; // FIXME: Horde_Share

        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Pastie_Driver_Sql#getPaste(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_db->query($query, $values);

        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if ($row instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($row);
        }
        $result->free();

        if ($row) {
            return array(
                'id' => $row['paste_id'],
                'uuid' => $row['paste_uuid'],
                'bin' => $row['paste_bin'],
                'title' => $row['paste_title'],
                'syntax' => $row['paste_syntax'],
                'paste' => $row['paste_content'],
                'owner' => $row['paste_owner'],
                'timestamp' => new Horde_Date($row['paste_timestamp'])
            );
        } else {
            throw new Pastie_Exception(_("Invalid paste ID."));
        }
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @throws Horde_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return;
        }

        Horde::assertDriverConfig($this->_params, 'storage',
                                  array('phptype', 'charset'));

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }

        /* Connect to the SQL server using the supplied parameters. */
        $this->_write_db = DB::connect($this->_params,
                                       array('persistent' => !empty($this->_params['persistent'])));
        if ($this->_write_db instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($this->_write_db);
        }

        // Set DB portability options.
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;

        default:
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = DB::connect($params,
                                     array('persistent' => !empty($params['persistent'])));
            if ($this->_db instanceof PEAR_Error) {
                throw new Horde_Exception_Prior($this->_db);
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;

            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db = $this->_write_db;
        }

        $this->_connected = true;
    }

    /**
     * Disconnects from the SQL server and cleans up the connection.
     */
    protected function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            $this->_db->disconnect();
            $this->_write_db->disconnect();
        }
    }

}
