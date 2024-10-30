<?php
    // total page count calculation
    $pages = ((int) ceil($total / $rpp));

    // if it's an invalid page request
    if ($current < 1) {
        return;
    } elseif ($current > $pages) {
        return;
    }
    //print_r($get);
    $max = min($pages, $crumbs);


            $limit = ((int) floor($max / 2));
            $leading = $limit;
            

            echo displayPaginationBelow($max,$current,$pages,$get,$key,$classes);



    // if there are pages to be shown
  

/* 
 * per page no of result, current_page, Total row, 
*/
function displayPaginationBelow($per_page,$page,$total,$get,$key,$classes){
	       
        

        //$classes = array('copy', 'previous');
        $params = $get;
        unset($params[$key]);
        $href = ($target) . '?' . http_build_query($params);
        $page_url= $href = preg_replace(
            array('/=$/', '/=&/'),
            array('', '&'),
            $href
        );

        $adjacents = "2"; 

    	$page = ($page == 0 ? 1 : $page);  
    	$start = ($page - 1) * $per_page;								
		
    	$prev = $page - 1;							
    	$next = $page + 1;
        $setLastpage = $total;
    	$lpm1 = $setLastpage - 1;
    	
    	$setPaginate = "";
    	if($setLastpage > 1)
    	{	
    		$setPaginate .= "<ul class='".implode(' ', $classes) ."'>";
                    $setPaginate .= "<li class='setPage'>Page $page of $setLastpage</li>";
    		if ($setLastpage < 7 + ($adjacents * 2))
    		{	
    			for ($counter = 1; $counter <= $setLastpage; $counter++)
    			{
    				if ($counter == $page)
    					$setPaginate.= "<li class='active'><a class='current_page '>$counter</a></li>";
    				else
    					$setPaginate.= "<li><a href='{$page_url}&paged=$counter'>$counter</a></li>";					
    			}
    		}
    		elseif($setLastpage > 5 + ($adjacents * 2))
    		{
    			if($page < 1 + ($adjacents * 2))		
    			{
    				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
    				{
    					if ($counter == $page)
    						$setPaginate.= "<li class='active'><a class='current_page'>$counter</a></li>";
    					else
    						$setPaginate.= "<li><a href='{$page_url}&paged=$counter'>$counter</a></li>";					
    				}
    				$setPaginate.= "<li class='dot'>...</li>";
    				$setPaginate.= "<li><a href='{$page_url}&paged=$lpm1'>$lpm1</a></li>";
    				$setPaginate.= "<li><a href='{$page_url}&paged=$setLastpage'>$setLastpage</a></li>";		
    			}
    			elseif($setLastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
    			{
    				$setPaginate.= "<li><a href='{$page_url}&paged=1'>1</a></li>";
    				$setPaginate.= "<li><a href='{$page_url}&paged=2'>2</a></li>";
    				$setPaginate.= "<li class='dot'>...</li>";
    				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
    				{
    					if ($counter == $page)
    						$setPaginate.= "<li  class='active'><a class='current_page'>$counter</a></li>";
    					else
    						$setPaginate.= "<li><a href='{$page_url}&paged=$counter'>$counter</a></li>";					
    				}
    				$setPaginate.= "<li class='dot'>..</li>";
    				$setPaginate.= "<li><a href='{$page_url}&paged=$lpm1'>$lpm1</a></li>";
    				$setPaginate.= "<li><a href='{$page_url}&paged=$setLastpage'>$setLastpage</a></li>";		
    			}
    			else
    			{
    				$setPaginate.= "<li><a href='{$page_url}&paged=1'>1</a></li>";
    				$setPaginate.= "<li><a href='{$page_url}&paged=2'>2</a></li>";
    				$setPaginate.= "<li class='dot'>..</li>";
    				for ($counter = $setLastpage - (2 + ($adjacents * 2)); $counter <= $setLastpage; $counter++)
    				{
    					if ($counter == $page)
    						$setPaginate.= "<li class='active'><a class='current_page'>$counter</a></li>";
    					else
    						$setPaginate.= "<li><a href='{$page_url}&paged=$counter'>$counter</a></li>";					
    				}
    			}
    		}
    		
    		if ($page < $counter - 1){ 
    			$setPaginate.= "<li><a href='{$page_url}&paged=$next'>Next</a></li>";
                $setPaginate.= "<li><a href='{$page_url}&paged=$setLastpage'>Last</a></li>";
    		}else{
    			$setPaginate.= "<li class='active'><a class='current_page'>Next</a></li>";
                $setPaginate.= "<li class='active'><a class='current_page'>Last</a></li>";
            }

    		$setPaginate.= "</ul>\n";		
    	}
    
    
        return $setPaginate;
 } 
