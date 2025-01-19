<x-filament-panels::page>

    <div>
        <h2 class="text-2xl font-bold mb-4">Organizational Chart</h2>
        <div id="chartContainer" style="width: 100%; height: 600px;"></div>

        <script src="https://releases.jquery.com/git/jquery-git.js"></script>
        <!-- Include OrgChart.js -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/orgchart/5.0.0/js/jquery.orgchart.min.js"
            integrity="sha512-IUNqrYw8R7mj0iBzb0FOTGTgEFrxZCHVCHnePUEmcjJ/XQE/0sqRhBmGpp20N2lVzAkIBs0Sz+ibRN8/W9YFnQ=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/orgchart/5.0.0/css/jquery.orgchart.css"
            integrity="sha512-5n6uZMAXFfsFB/7EnP7/6HwUOLpWGtSuYZMg9lM7K+RRhDmQoKQOUABjRn+Pl8MdhaXBdwmxB/j0aivqOLryOw=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var data = @json($this->getEmployees());

                var chart = new OrgChart(document.getElementById("chartContainer"), {
                    template: "ana",
                    nodeBinding: {
                        field_0: "name",
                    },
                    nodes: convertToOrgChartNodes(data),
                    nodeColor: "#D3D3D3",
                    nodeBorderColor: "#000000",
                    nodeBorderWidth: 1,
                    nodePadding: 20,
                    // Add more configurations here
                });

                function convertToOrgChartNodes(data) {
                    var nodes = [];

                    function recurse(node) {
                        var newNode = {
                            id: node.name,
                            name: node.name
                        };
                        if (node.children && node.children.length > 0) {
                            newNode.children = [];
                            node.children.forEach(child => {
                                newNode.children.push(recurse(child));
                            });
                        }
                        return newNode;
                    }

                    data.forEach(item => {
                        nodes.push(recurse(item));
                    });

                    return nodes;
                }
            });
        </script>
    </div>
</x-filament-panels::page>
