<x-filament::page>
    {{-- زر الطباعة --}}
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ طباعة
        </button>
    </div>

    {{-- شجرة الحسابات --}}
    <div id="reportContent" class="text-sm">
        <h2 class="text-lg font-bold mb-4">📂 شجرة الحسابات</h2>
        <ul class="tree list-none space-y-1">
            @php
                $renderTree = function ($account, $level = 0) use (&$renderTree, $suppliersParentId, $suppliers) {
                    $hasChildren = $account->children->isNotEmpty();
                    $isSuppliersAccount = $suppliersParentId && $account->id === $suppliersParentId;

                    echo '<li>';
                    echo '<div class="tree-node flex items-center cursor-pointer group" onclick="toggleChildren(this)" style="padding-left: ' .
                        $level * 20 .
                        'px;">';
                    echo '<span class="mr-2 text-blue-600 font-bold group-open-indicator">' .
                        ($hasChildren || $isSuppliersAccount ? '➕' : '•') .
                        '</span>';
                    echo '<span class="font-mono text-sm text-gray-700">' . $account->formatted_code . '</span>';
                    echo '<span class="mx-2 text-gray-800">' . $account->name . '</span>';
                    echo '<span class="ml-auto text-xs px-2 py-1 rounded-full bg-gray-200">' .
                        ucfirst($account->type) .
                        '</span>';
                    echo '</div>';

                    // عرض أبناء الحساب الفعلي
                    if ($hasChildren) {
                        echo '<ul class="ml-4 mt-1 hidden">';
                        foreach ($account->children as $child) {
                            $renderTree($child, $level + 1);
                        }
                        echo '</ul>';
                    }

                    // عرض الموردين كأبناء وهميين للحساب التحليلي
                    if ($isSuppliersAccount && count($suppliers)) {
                        echo '<ul class="ml-4 mt-1 hidden">';
                        foreach ($suppliers as $supplier) {
                            echo '<li>';
                            echo '<div class="tree-node flex items-center" style="padding-left: ' .
                                ($level + 1) * 20 .
                                'px;">';
                            echo '<span class="mr-2 text-gray-400">•</span>';
                            echo '<span class="font-mono text-sm text-gray-600">SUP-' . $supplier->id . '</span>';
                            echo '<span class="mx-2 text-gray-700">' . $supplier->name . '</span>';
                            echo '<span class="ml-auto text-xs px-2 py-1 rounded-full bg-gray-100">Supplier</span>';
                            echo '</div>';
                            echo '</li>';
                        }
                        echo '</ul>';
                    }

                    echo '</li>';
                };
            @endphp

            @foreach ($accounts as $account)
                {!! $renderTree($account) !!}
            @endforeach
        </ul>
    </div>

    {{-- سكربت الطباعة --}}
    <script>
        function toggleChildren(el) {
            const indicator = el.querySelector('.group-open-indicator');
            const nextUl = el.nextElementSibling;

            if (nextUl && nextUl.tagName === 'UL') {
                nextUl.classList.toggle('hidden');
                if (indicator) {
                    indicator.textContent = nextUl.classList.contains('hidden') ? '➕' : '➖';
                }
            }
        }

        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent").innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>

    {{-- CSS للتنسيق --}}
    <style>
        .tree-node:hover {
            background-color: #f9fafb;
        }

        .tree li {
            list-style-type: none;
        }

        .tree ul {
            margin-left: 1rem;
            padding-left: 0;
        }

        @media print {
            #printReport {
                display: none;
            }

            .tree ul {
                display: block !important;
            }
        }
    </style>
</x-filament::page>
