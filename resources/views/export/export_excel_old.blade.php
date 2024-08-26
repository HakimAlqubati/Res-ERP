<table>
    <thead>
        <tr>
            <th> Order ID </th>
            <th>#<?php echo $finalResult[0]->orderId; ?></th>
            <th> Created by </th>
            <th colspan="2"> {{ $finalResult[0]->createdByUserName }}</th>
            <th> Date </th>
            <th>{{ $finalResult[0]->createdAt }}</th>
        </tr>
        <tr>
            <th>#</th>
            <th>Product id </th>
            <th>Product name </th>
            <th>Product code </th>
            <th>Product Description </th>
            <th>Unit </th>
            <th>Quantity</th>
            <th>Price</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total_price = 0;
       
        foreach($finalResult as $key => $value) { 
            $index = 0;
          
            if( is_numeric ($key) && ((int)$key) > 0) {
            ?>
        <tr>
            <td>{{ $key }}</td>
            <td>{{ $value->product_id ?? '--' }}</td>
            <td>{{ $value->product_name ?? '--' }}</td>
            <td>{{ $value->product_code ?? '--' }}</td>
            <td>{{ $value->product_desc ?? '--' }}</td>
            <td>{{ $value->unit_name ?? '--' }}</td>
            <td>{{ $value->qty ?? '--' }}</td>
            <td>{{ $value->price ?? '--' }}</td>
        </tr>
        <?php
        if( is_numeric ($value->price )) {

            $total_price += $value->price ;
        }
            }
     } ?>
        <tr style=" font-weight: 700;">

            <td colspan="7" style="text-align: center">
                Total price for orrder ID : #<?php echo $finalResult[0]->orderId; ?>
            </td>
            <td>
                <?php echo (int) $total_price; ?>
            </td>
        </tr>

        <tr style="height: 110px;     font-weight: 700;">
            <td style="text-align: center" colspan="4" rowspan="3">Store manager: <h6><?php echo $finalResult[0]->manager_name; ?> </h6>
            </td>

            <td style="text-align: center" colspan="4" rowspan="3"> Created by:
                <h6> <?php echo $finalResult[0]->createdByUserName; ?> -
                    Manager of branch: <?php echo $finalResult[0]->branch_name; ?>
                </h6>
            </td>
        </tr>


    </tbody>
</table>
