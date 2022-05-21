<?php

require_once __DIR__ . "/../vendor/autoload.php";

use spyfly\Plugins\Tst2Lrs\Utils\Tst2LrsTrait;
use ILIAS\DI\Container;
use srag\CustomInputGUIs\Tst2Lrs\Loader\CustomInputGUIsLoaderDetector;
use srag\DevTools\Tst2Lrs\DevToolsCtrl;
use srag\RemovePluginDataConfirm\Tst2Lrs\PluginUninstallTrait;
use srag\DIC\Tst2Lrs\DICTrait;

/**
 * Class ilTst2LrsPlugin
 *
 * Generated by SrPluginGenerator v2.8.1
 *
 * @author Sebastian Heiden <test2lrs@spyfly.xyz>
 * @author studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class ilTst2LrsPlugin extends ilEventHookPlugin
{

    use PluginUninstallTrait;
    use Tst2LrsTrait;
    use DICTrait;

    const PLUGIN_CLASS_NAME = self::class;
    const PLUGIN_ID = "tst2lrs";
    const PLUGIN_NAME = "Tst2Lrs";
    /**
     * @var self|null
     */
    protected static $instance = null;


    /**
     * ilTst2LrsPlugin constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    public static function main(string $active_id, string $pass, string $obj_id, string $user_id, string $a_event)
    {
        self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | Fetching Test Data for active_id: ' . $active_id);
        global $DIC;
        $ilTestObj = new ilObjTest($obj_id, false);
        $ilUsrObj = new ilObjUser($user_id);
        
        $pass_details = null;
        $test_details = null;
        $ilTestServiceGui = new ilObjTestGUI();
        $answers = $ilTestObj->getTestResult($active_id, $pass);
        foreach ($answers as $key => $values) {
            if ($key === 'pass') {
                $pass_details = $values;
            } else if ($key === 'test') {
                $test_details = $values;
            }
            self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | Values for "'.$key.'" ' . print_r($values, true));
            /*if ($values['qid']) {
                $questionUi = $ilTestServiceGui->object->createQuestionGUI("", $values['qid']);
                $solutions = $questionUi->getSolutionOutput($active_id, $pass, false, true, true, true, false);
                self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | Solution: ' . print_r($solutions, true));
            }*/
        }
        self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | Fetched Test Data!');
        //self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | ' . print_r($answers, true));
        /*
        self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | ' . var_export($testSequence, true));
        self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | ' . var_export($testSession, true));
        self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | ' . print_r($testData, true));
        */

        $xapiStatementList = new ilLp2LrsXapiStatementList();

        $lrsType = new ilCmiXapiLrsType('1');
        $xapiStatement = new ilTst2LrsXapiStatement($lrsType, $ilTestObj, $ilUsrObj, $a_event, $pass_details, $test_details);
        self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | xAPI Statement: ' . json_encode($xapiStatement));

        $xapiStatementList->addStatement($xapiStatement);

        /* Send Data to LRS */
        $lrsRequest = new ilLp2LrsXapiRequest(
			ilLoggerFactory::getRootLogger(),
			$lrsType->getLrsEndpointStatementsLink(),
			$lrsType->getLrsKey(),
			$lrsType->getLrsSecret()
		);

        $lrsRequest->send($xapiStatementList);
    }

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * @inheritDoc
     */
    public function exchangeUIRendererAfterInitialization(Container $dic): Closure
    {
        return CustomInputGUIsLoaderDetector::exchangeUIRendererAfterInitialization();
    }


    /**
     * @inheritDoc
     */
    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }


    /**
     * @inheritDoc
     */
    public function handleEvent(/*string*/$a_component, /*string*/ $a_event, /*array*/ $a_parameter): void
    {
        // TODO: Implement handleEvent
        self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | C: ' . $a_component . ' | E: ' . $a_event . ' | P: ' . json_encode($a_parameter));
        if ($a_event === 'finishTestPass') {
            $this::main($a_parameter['active_id'], $a_parameter['pass'], $a_parameter['obj_id'], $a_parameter['user_id'], $a_event);
        }
    }


    /**
     * @inheritDoc
     */
    public function updateLanguages(/*?array*/$a_lang_keys = null): void
    {
        parent::updateLanguages($a_lang_keys);

        $this->installRemovePluginDataConfirmLanguages();
    }


    /**
     * @inheritDoc
     */
    protected function deleteData(): void
    {
        self::tst2Lrs()->dropTables();
    }


    /**
     * @inheritDoc
     */
    protected function shouldUseOneUpdateStepOnly(): bool
    {
        return false;
    }
}
