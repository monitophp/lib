<?php
namespace MonitoLib;

use Composer\Script\Event;

class Install
{
	public static function postComposer (Event $event)
	{
		try {
            $baseDir  = dirname(str_replace('/', '\\', $event->getComposer()->getConfig()->get('vendor-dir'))) . DIRECTORY_SEPARATOR;
            $filesDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

            $option = [
                'overwrite' => false,
                'dir' => null,
            ];

            $files = [
                '.gitignore' => [
                ],
                '.htaccess' => [],
                'database.json' => [
                    'dir' => 'config',
                ],
                'init.php' => [
                    'dir' => 'config',
                ],
                'index.php' => [
                    'overwrite' => true,
                ]
            ];

            foreach ($files as $fileName => $options) {
                $sourceFile = $filesDir . $fileName;

                if ($sourceFile) {
                    echo "Arquivo $sourceFile nao encontrado\n";
                    continue;
                }

                $destination = $baseDir;
                $options = array_merge($options, $option);

                // Verifica se o arquivo deve ser copiado para um diretório diferente de root
                if (!is_null($options['dir'])) {
                    $destination .= $options['dir'] . DIRECTORY_SEPARATOR;

                    if (!file_exists($destination)) {
                        if (!mkdir($destination)) {
                            throw new \Exception("Erro ao criar o diretorio $destination!");
                        }
                    }
                }

                $destinationFile = $destination . $fileName;

                // Ignora o arquivo se existir e não permitir sobreescrever
                if (file_exists($destinationFile) && !$options['overwrite']) {
                    echo "Arquivo $destinationFile ignorado\n";
                    continue;
                }
                
                // Verifica se o arquivo de destino foi excluído
                if (file_exists($destinationFile) && !unlink($destinationFile)) {
                    throw new \Exception("Erro ao excluir o arquivo $destinationFile!");
                }

                // Copia o arquivo
                if (!copy($sourceFile, $destinationFile)) {
                    throw new \Exception("Erro ao criar o arquivo $destinationFile!");
                }
            }
    	} catch (Exception $e) {
            echo "Ocorreu um error ao executar o script:\n";
            echo $e->getMessage() . "\n";
    	}
	}
}