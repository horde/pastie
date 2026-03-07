<?php

/**
 * This class provides the application configuration for the test script.
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 * @coversNothing
 */
class Pastie_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = [];

    /**
     * PHP settings list.
     *
     * @var array
     */
    protected $_settingsList = [];

    /**
     * PEAR modules list.
     *
     * @var array
     */
    protected $_pearList = [];

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = [];

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = [];

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests() {}
}
