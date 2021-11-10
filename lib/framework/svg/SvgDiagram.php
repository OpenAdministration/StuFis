<?php

namespace framework\svg;

/**
 * factory class for diagram generation
 * Factory
 * @author 		Michael Gnehr <michael@gnehr.de>
 * @category    framework
 * @since 		09.08.2016
 * @version 	02.0.0 since 01.07.2018
 * @copyright 	Copyright (C) 2016-Today - All rights reserved - do not copy or reditribute
 */
class SvgDiagram
{
    /* ------ DIAGRAM TYPES ------ */
    /**
     * class constansts -> diagramm type: BLOCK
     * @var string
     */
    public const TYPE_BLOCK = 'Block';
    /**
     * class constansts -> diagramm type: ADDING BEAM (BLOCK)
     * @var string
     */
    public const TYPE_ADDINGBLOCK = 'AddingBeam';
    /**
     * class constansts -> diagramm type: PIE
     * @var string
     */
    public const TYPE_PIE = 'Pie';
    /**
     * class constansts -> diagramm type: LINE
     * @var string
     */
    public const TYPE_LINE = 'Line';
    /**
     * class constansts -> diagramm type: STATE
     * @var string
     */
    public const TYPE_STATE = 'State';
    /**
     * class constansts -> diagramm type: RAW
     * draw svg manually
     * @var string
     */
    public const TYPE_RAW = 'Raw';
    /**
     * class constansts -> diagramm type: DUMMY
     * @var string
     */
    public const TYPE_DUMMY = 'Dummy';
    /**
     * class constansts -> diagramm type: NONE
     * default value
     * @var string
     */
    public const TYPE_NONE = 'None';

    // private member variables -------------------------------

    /**
     * list of valid diagram types
     * @var array
     */
    private static $types = [
        'SvgDiagramDummy' => 'Dummy',
        'SvgDiagramNone' => 'Dummy',
        'SvgDiagramPlaceholder' => 'Dummy',
        'SvgDiagramBlock' => 'Block',
        'SvgDiagramAddingBeam' => 'AddingBeam',
        'SvgDiagramPie' => 'Pie',
        'SvgDiagramLine' => 'Line',
        'SvgDiagramState' => 'State',
        'SvgDiagramRaw' => 'Raw',
    ];

    /**
     * private constructor
     */
    private function __construct()
    {
    }

    /**
     * return list of diagram types
     */
    public static function getTypes(): array
    {
        $out = [];
        foreach (self::$types as $t) {
            $out[$t] = $t;
        }
        return array_values($out);
    }

    /**
     * return Svg Diagram by type name
     * @param string Svg$type
     * @return static -> inherited class object FIXME: in php8
     */
    public static function newDiagram(string $type)
    {
        if (mb_strpos($type, 'SvgDiagram') === 0) {
            $type = str_replace('SvgDiagram', '', $type);
        }
        $c = SvgDiagramDummy::class;
        $idx = 'SvgDiagram' . $type;
        if (isset(self::$types[$idx])) {
            $c = __CLASS__ . self::$types[$idx];
        }

        return new $c($type);
    }
}
