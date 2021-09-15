# App
Estática

##  Constantes

    const VERSION = '1.2.0';
    /**
    * 1.2.0 - 2019-09-12
    * new: today
    *
    * 1.1.0 - 2019-06-05
    * new: createPath, getDocumentRoot
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    const DS = DIRECTORY_SEPARATOR;

    static private $debug = 0;
    static private $instance;
    static private $cachePath;
    static private $configPath;
    static private $hasPrivileges = false;
    static private $isLoggedIn = false;
    static private $logPath;
    static private $storagePath;
    static private $tmpPath;

## Métodos

    ### createPath ($path)
    ### getCachePath ($relativePath = null)
    ### getConfigPath ($relativePath = null)
    ### getDebug ()
    ### getDocumentRoot ()
    ### getInstance ()
    ### getLogPath ($relativePath = null)
    ### getStoragePath ($relativePath = null)
    ### getTmpPath ($relativePath = null)
    ### now ()
    Retorna o tempo atual no formato Y-m-d H:i:s
    ### run ()
    ### setDebug ($debug)
    ### setHasPrivileges ($hasPrivileges)
    ### setIsLoggedIn ($isLoggedIn)
    ### today ()
