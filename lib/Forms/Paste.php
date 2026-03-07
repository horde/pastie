<?php

/**
 * Copyright 2010-2026 Alkaloid Networks LLC <http://www.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Pastie
 */
class PasteForm extends Horde_Form
{
    /**
     */
    public function PasteForm($vars)
    {
        parent::__construct($vars, _("New Paste"));

        $engine = 'Pastie_Highlighter_' . $GLOBALS['conf']['highlighter']['engine'];
        $tmp = call_user_func([$engine, 'getSyntaxes']);
        $types = [];
        foreach ($tmp as $type) {
            $types[$type] = $type;
        }

        // Some highlighters have a long list of supported languages.
        // Default to PHP if one is not already specified
        $curtype = $vars->get('syntax');
        if (empty($curtype)) {
            $vars->set('syntax', 'php');
        }

        $this->addVariable(_("Title"), 'title', 'text', false);

        $this->addVariable(
            _("Syntax"),
            'syntax',
            'enum',
            true,
            false,
            null,
            [$types, false]
        );

        $this->addVariable(
            _("Paste"),
            'paste',
            'longtext',
            true,
            false,
            null,
            ['rows' => 20, 'cols' => 100]
        );

        return true;
    }
}
