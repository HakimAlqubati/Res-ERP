<x-filament-panels::page>

    <style>
        .highcharts-figure,
        .highcharts-data-table table {
            min-width: 360px;
            max-width: 600;
            margin: 1em auto;
        }

        .highcharts-data-table table {
            background-color: 'yellow';
            font-family: Verdana, sans-serif;
            border-collapse: collapse;
            border: 1px solid #EBEBEB;
            margin: 10px auto;
            text-align: center;
            width: 100%;
            max-width: 500px;
        }

        .highcharts-data-table caption {
            padding: 1em 0;
            font-size: 1.2em;
            color: #555;
        }

        .highcharts-data-table th {
            font-weight: 600;
            padding: 0.5em;
        }

        .highcharts-data-table td,
        .highcharts-data-table th,
        .highcharts-data-table caption {
            padding: 0.5em;
        }

        .highcharts-data-table thead tr,
        .highcharts-data-table tr:nth-child(even) {
            background: #f8f8f8;
        }

        .highcharts-data-table tr:hover {
            background: #f1f7ff;
        }

        #container h4 {
            text-transform: none;
            font-size: 14px;
            font-weight: normal;
        }

        #container p {
            font-size: 13px;
            line-height: 16px;
        }

        @media screen and (max-width: 600px) {
            #container h4 {
                font-size: 2.3vw;
                line-height: 3vw;
            }

            #container p {
                font-size: 2.3vw;
                line-height: 3vw;
            }
        }

        #container {
            max-width: 800px;
            margin: 1em auto;
        }
    </style>

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/sankey.js"></script>
    <script src="https://code.highcharts.com/modules/organization.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>





    <figure class="highcharts-figure">
        <div id="container"></div>
        <p class="highcharts-description">
            Organization charts are a common case of hierarchical network charts,
            where the parent/child relationships between nodes are visualized.
            Highcharts includes a dedicated organization chart type that streamlines
            the process of creating these types of visualizations.
        </p>
    </figure>
    <script>
        Highcharts.chart('container', {
            chart: {
                height: 400,
                inverted: true
            },


            title: {
                text: 'Workbench Org Chart'
            },

            accessibility: {
                point: {
                    descriptionFormatter: function(point) {
                        var nodeName = point.toNode.name,
                            nodeId = point.toNode.id,
                            nodeDesc = nodeName === nodeId ? nodeName : nodeName + ', ' + nodeId,
                            parentDesc = point.fromNode.id;
                        return point.index + '. ' + nodeDesc + ', reports to ' + parentDesc + '.';
                    }
                }
            },

            series: [{
                type: 'organization',
                name: 'Highsoft',
                keys: ['from', 'to'],
                allowPointSelect: true,
                borderColor: '#666666',
                borderRadius: 2,

                getExtremesFromAll: false,
                data: [
                    [1, 2],
                    [1, 22],
                    [1, 222],
                    [1, 2222],
                    [1, 22222],
                    [1, 222222],
                    [1, 2222222],
                    [1, 22222222],
                    [1, 222222222],
                    [1, 2222222222],
                    [1, 22222222222],
                    [1, 222222222222],
                    [1, 2222222222222],
                    [1, 22222222222222],
                    [1, 222222222222222],
                    [1, 2222222222222222],
                    [1, 22222222222222222],
                    [1, 222222222222222222],
                    [1, 2222222222222222222],
                    [1, 3],
                    [1, 4],
                    [1, 5],
                    [1, 6],
                    [6, 7],
                    [7, 8],
                    [7, 9],
                ],
                levels: [{
                    level: 1,
                    color: '# 980104 '
                }, {
                    level: 4,
                    color: '#359154'
                }],
                events: {
                    
                },
                nodes: [{
                    id: 1,
                    title: 1,
                    name: 'Grethe Hjetland',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131126/Highsoft_03862_.jpg'
                }, {
                    id: 5,
                    title: 'HR/CFO',
                    name: 'Anne Jorunn Fjærestad',
                    color: '#007ad0',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131210/Highsoft_04045_.jpg'
                }, {
                    id: 6,
                    title: 'KDKDKDK',
                    name: 'Hakimoooooooooooooo',
                    color: '#007ad0',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131210/Highsoft_04045_.jpg'
                }, {
                    id: 7,
                    title: 'ds',
                    name: 'zzzzzzzzzzzzzz',
                    color: '#007ad0',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131210/Highsoft_04045_.jpg'
                }, {
                    id: 8,
                    title: 'Hi Hi Hi',
                    name: 'xxxxxxxxxxxxxxxxxxxxxxxxxx',
                    color: '#007ad0',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131210/Highsoft_04045_.jpg'
                }, {
                    id: 9,
                    title: 'Ah Ah Ah',
                    name: 'xxxxxxxxxxxxxxxxxxxxxxxxxx',
                    color: '#007ad0',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131210/Highsoft_04045_.jpg'
                }, {
                    id: 2,
                    title: 2,
                    name: 'Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 22,
                    title: 22,
                    name: '22Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 222,
                    title: 222,
                    name: '222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 2222,
                    title: 2222,
                    name: '2222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 22222,
                    title: 22222,
                    name: '22222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 222222,
                    title: 222222,
                    name: '222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 2222222,
                    title: 2222222,
                    name: '2222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 22222222,
                    title: 22222222,
                    name: '22222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 222222222,
                    title: 222222222,
                    name: '222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 2222222222,
                    title: 2222222222,
                    name: '2222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 22222222222,
                    title: 22222222222,
                    name: '22222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 222222222222,
                    title: 222222222222,
                    name: '222222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 2222222222222,
                    title: 2222222222222,
                    name: '2222222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 22222222222222,
                    title: 22222222222222,
                    name: '22222222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 222222222222222,
                    title: 222222222222222,
                    name: '222222222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 2222222222222222,
                    title: 2222222222222222,
                    name: '2222222222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 22222222222222222,
                    title: 22222222222222222,
                    name: '22222222222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 222222222222222222,
                    title: 222222222222222222,
                    name: '222222222222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 2222222222222222222,
                    title: 2222222222222222222,
                    name: '2222222222222222222Christer Vasseng',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131120/Highsoft_04074_.jpg'
                }, {
                    id: 3,
                    title: 3,
                    name: 'Torstein Hønsi',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131213/Highsoft_03998_.jpg'
                }, {
                    id: 4,
                    title: 4,
                    name: 'Anita Nesse',
                    image: 'https://wp-assets.highcharts.com/www-highcharts-com/blog/wp-content/uploads/2020/03/17131156/Highsoft_03834_.jpg'
                }],
                colorByPoint: false,
                color: '#007ad0',
                dataLabels: {
                    color: 'white'
                },
                borderColor: 'white',
                nodeWidth: 65
            }],
            tooltip: {
                outside: true
            },
            exporting: {
                allowHTML: true,
                sourceWidth: 800,
                sourceHeight: 600
            }

        });
    </script>
</x-filament-panels::page>
