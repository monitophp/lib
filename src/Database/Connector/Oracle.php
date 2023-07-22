<?php
/**
 * Database\Connector\Oracle.
 *
 * @version 1.2.0
 */

namespace MonitoLib\Database\Connector;

use MonitoLib\Exception\DatabaseErrorException;

class Oracle extends Connection
{
    private $executeMode;

    public function connect()
    {
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
        $password = ml_decrypt($this->pass, $this->name . $this->env);

        $this->connection = @oci_connect($this->user, $password, $this->host, $this->charset);

        if (!$this->connection) {
            $db = debug_backtrace();
            $e = oci_error();

            $error = [
                'message' => $e['message'],
                'file' => $db[1]['file'],
                'line' => $db[1]['line'],
            ];

            throw new DatabaseErrorException('Error connecting to database', $error);
        }
    }

    public function beginTransaction()
    {
        $this->executeMode = OCI_NO_AUTO_COMMIT;
    }

    public function commit()
    {
        @oci_commit($this->conn);
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
    }

    public function execute($stt)
    {
        $exe = @oci_execute($stt, $this->executeMode);

        if (!$exe) {
            $e = @oci_error($stt);

            throw new DatabaseErrorException('An error has occurred on database', $e);
        }

        return $stt;
    }

    public function fetchArrayAssoc($stt)
    {
        return oci_fetch_array($stt, OCI_ASSOC | OCI_RETURN_NULLS);
    }

    public function fetchArrayNum($stt)
    {
        return oci_fetch_array($stt, OCI_NUM | OCI_RETURN_NULLS);
    }

    public function parse($sql)
    {
        $stt = @oci_parse($this->conn, $sql);

        if (!$stt) {
            $e = @oci_error($stt);

            throw new DatabaseErrorException('An error has occurred on database', $e);
        }

        return $stt;
    }

    public function rollback()
    {
        @oci_rollback($this->conn);
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
    }

    public function transform($function)
    {
        switch ($function) {
            case 'UPPERCASE':
                return 'UPPER';

            default:
                return $function;
        }
    }
}
