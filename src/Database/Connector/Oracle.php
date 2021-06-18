<?php 
/**
 * Database connector
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2013 - 2018
 *  
 * @package MonitoLib
 */
namespace MonitoLib\Database\Connector;

use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Oracle extends Connection
{
    const VERSION = '1.1.0';
    /**
    * 1.1.0 - 2020-07-21
    * new: encrypted password
    *
    * 1.0.1 - 2019-05-02
    * new: exception on parse error
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    private $executeMode;

	public function connect()
	{
		$this->executeMode = OCI_COMMIT_ON_SUCCESS;
        $password = Functions::decrypt($this->pass, $this->name . $this->env);

		$this->connection = @oci_connect($this->user, $password, $this->host, 'AL32UTF8');

		if (!$this->connection) {
			$db = debug_backtrace();
			$e  = oci_error();

			$error = [
                'message' => $e['message'],
                'file'    => $db[1]['file'],
                'line'    => $db[1]['line'],
			];

			throw new DatabaseError('Erro ao conectar no banco de dados!', $error);
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
            throw new DatabaseError('Ocorreu um erro no banco de dados!', $e);
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
            throw new DatabaseError('Ocorreu um erro no banco de dados!', $e);
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