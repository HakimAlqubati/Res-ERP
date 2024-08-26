<table>
    <thead>
        <tr>
            <th>Product code </th>
            <th>Product name </th>
            <th>Unit </th>
            <th>Quantity</th> 
        </tr>
    </thead>
    <tbody>
        <?php  
       
        foreach($finalResult as $key => $value) { 
             
          
            if( is_numeric ($key) && ((int)$key) > 0) {
            ?>
        <tr>
            <td>{{ $value?->product_code }}</td>
            <td>{{ $value?->product_name }}</td>
            <td>{{ $value?->unit_name }}</td>
            <td>{{ $value?->available_qty }}</td>
        </tr>
        <?php
       
            }
     } ?>



    </tbody>
</table>
