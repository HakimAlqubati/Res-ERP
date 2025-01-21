<x-filament-panels::page>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        .tree ul {
            padding-top: 20px;
            position: relative;

            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
        }

        .tree li {
            float: left;
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 20px 5px 0 5px;

            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
        }

        /*We will use ::before and ::after to draw the connectors*/

        .tree li::before,
        .tree li::after {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 1px solid #ccc;
            width: 50%;
            height: 20px;
        }

        /*We need to remove left-right connectors from elements without
any siblings*/
        .tree li:only-child::after,
        .tree li:only-child::before {
            display: none;
        }

        /*Remove space from the top of single children*/
        .tree li:only-child {
            padding-top: 0;
        }


        /*Time to add downward connectors from parents*/
        .tree ul ul::before {
            content: '';
            position: absolute;
            top: 0;
            border-left: 1px solid #ccc;
            width: 0;
            height: 20px;
        }

        .tree li a {
            border: 1px solid #ccc;
            padding: 5px 10px;
            text-decoration: none;
            color: #fff;
            font-family: arial, verdana, tahoma;
            font-size: 11px;
            display: inline-block;

            border-radius: 5px;
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;

            background: linear-gradient(45deg, #3e8e41, #3e6e91);

            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
        }

        /*Time for some hover effects*/
        /*We will apply the hover effect the the lineage of the element also*/
        .tree li a:hover,
        .tree li a:hover+ul li a {
            background: #c8e4f8;
            color: #000;
            border: 1px solid #94a0b4;
        }

        /*Connector styles on hover*/
        .tree li a:hover+ul li::after,
        .tree li a:hover+ul li::before,
        .tree li a:hover+ul::before,
        .tree li a:hover+ul ul::before {
            border-color: #94a0b4;
        }

        .tree ul ul ul ul li a {
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 10px;
            text-decoration: none;
            color: #666;
            font-family: arial, verdana, tahoma;
            font-size: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
            text-orientation: mixed !important;
            white-space: nowrap;
            /* Prevent text from wrapping */

            writing-mode: vertical-rl;
            /* Vertical orientation */
        }



        .tree li::after {
            right: auto;
            left: 50%;
            border-left: 1px solid #ccc;
        }

        .tree ul ul ul::before {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -20px;
            border-left: 1px solid #ccc;
            height: 20px;
        }

        /*Remove left connector from first child and
  right connector from last child*/
        .tree li:first-child::before,
        .tree li:last-child::after {
            border: 0 none;
        }

        /*Adding back the vertical connector to the last nodes*/
        .tree li:last-child::before {
            border-right: 1px solid #ccc;
            border-radius: 0 5px 0 0;
            -webkit-border-radius: 0 5px 0 0;
            -moz-border-radius: 0 5px 0 0;
        }

        .tree li:first-child::after {
            border-radius: 5px 0 0 0;
            -webkit-border-radius: 5px 0 0 0;
            -moz-border-radius: 5px 0 0 0;
        }


        @media (max-width: 600px) {
            .tree ul ul ul ul li a {
                writing-mode: horizontal-tb;
            }

            .tree ul ul ul {
                padding-top: 0;
            }

            .tree>ul>li>ul>li>ul>li {
                padding: 0 0 0 20px;
                display: flex;
                align-items: center;
                clear: left;
                position: relative;
                left: 50%;
            }

            .tree>ul>li>ul>li>ul>li::before {
                height: 100%;
            }

            .tree>ul>li>ul>li>ul>li:last-child::before {
                height: 50%;
            }

            .tree>ul>li>ul>li>ul>li>a {
                writing-mode: vertical-rl;
            }


            .tree ul ul ul li::before {
                width: 20px;
                left: 0;
                border-left: 1px solid #ccc !important;
                border-top: none;
            }

            .tree ul ul ul li:last-child::before {
                border-bottom: 1px solid #ccc;
                border-right: none;
                border-radius: 0 0 0 5px;
                -webkit-border-radius: 0 0 0 5px;
                -moz-border-radius: 0 0 0 5px;
            }

            .tree ul ul ul li::after {
                width: 20px;
                top: 50%;
                left: 0;
                border-left: 1px solid #ccc;

                border-radius: 0 !important;
                -webkit-border-radius: 0 !important;
                -moz-border-radius: 0 !important;
            }

            .tree ul ul ul ul {
                display: inline-block;
                padding: 0 0 0 20px;
            }

            .tree ul ul ul ul::before {
                border-left: none;
                border-top: 1px solid #ccc;
                width: 20px;
                left: 0;
                top: 50%;
            }

            .tree ul ul ul ul li {
                float: none;
                padding: 5px 0 5px 20px;
            }

            .tree ul ul ul ul li:first-child::before,
            .tree ul ul ul li:last-child::after {
                border: 0 none !important;
            }

            .tree ul ul ul ul li:first-child::after {
                border-radius: 5px 0 0 0 !important;
                -webkit-border-radius: 5px 0 0 0 !important;
                -moz-border-radius: 5px 0 0 0 !important;
            }
        }

        .outer {
            overflow-x: auto;
            /* Enables horizontal scrolling if content overflows */
            border: 1px solid #ccc;
            padding: 10px;
            width: 100%;
        }

        .tree {
            display: inline-block;
            /* Makes width adjust to the content dynamically */
            white-space: nowrap;
            /* Ensures child elements stay on one line and don't wrap */
            text-align: center;
            width: 300%;
        }
    </style>
    {{-- @if (is_string($this->generate()))
        <p>Please assign employees to the users first.</p>
    @else --}}
    <div class="outer">
        <div class="tree">
            {!! $this->generate() !!}
        </div>

    </div>
    {{-- @endif --}}

</x-filament-panels::page>
