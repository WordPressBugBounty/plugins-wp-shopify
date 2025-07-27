<?php

	add_action( 'wp_ajax_wpsy_graphql_central', 'wpsy_graphql_central' );
	
	if(!function_exists('wpsy_graphql_central')){
		function wpsy_graphql_central($posted=array(), $inner=false){
			
			$posted = (empty($posted)?sanitize_wpsy_data($_POST):$posted);
			$id = (array_key_exists('id', $posted)?$posted['id']:0);
			$limit = (array_key_exists('limit', $posted)?$posted['limit']:10);
			
			//pree($posted);exit;

			if ( !$inner && ! wp_verify_nonce( $posted['nonce'], 'wpsy-nonce' )) {
				 die ( 'Busted!');
			}else{
			
				$graphql = (array_key_exists('query', $posted)?$posted['query']:'');
				
				if($graphql){
					
					$query = '{
								shop  {
									name
									description
								}
							 }';
					
					switch($graphql){
						case 'products':
							$query = '
{
  products(first: '.$limit.') {
    edges {
      node {
        id
        title
        productType
        variants(first: 2) {
          edges {
            node {
              id
              title
            }
          }
        }
        priceRange{
          maxVariantPrice{
            amount
            currencyCode
          }
          minVariantPrice{
            amount
            currencyCode
          }
        }
        featuredImage{
          url
        }
        images(first:10){
          edges{
            node{
              url
            }
            
          }
        }
      }
    }
  }
}

							
';
						break;
						case 'product':
							
							$query = '
{
  product(id:"gid://shopify/Product/'.$id.'"){
	id
	handle
    title
	description
	descriptionHtml
    featuredImage{
		url	
	}
    priceRange{
      maxVariantPrice{
        amount
		currencyCode
      }
      minVariantPrice{
        amount
		currencyCode
      }
      
    }
	images(first:10){
	  edges{
		node{
		  url
		}
		
	  }
	}	
    variants(first: 15) {
      edges {
        node {
          id
          sku
          title
		  priceV2{
            amount
            currencyCode
          }
          image{
            id
            url
          }
        }
      }
    }	
	options{
      id
      name
      values      
    }
	
  }
}
							
							';
						break;
						case 'collection':
							$query = '
						
	
	
{
  collection(id:"gid://shopify/Collection/'.$id.'"){
   id
    title
	handle
    description
    descriptionHtml
	image{
		  url
	}	
    products(first:'.$limit.'){
      edges{
        node{
          id
          title
		  handle
		  description
		  onlineStoreUrl
		priceRange{
		maxVariantPrice
		{
		  amount
		  currencyCode
		}
		minVariantPrice{
		  amount
		  currencyCode              
		}
		}
		featuredImage{
			url	
		}		  
        }
      }
    }
  }
  }
							';
							
						break;
						default:
							
						break;
					}
						

					 
					$wpsy_db_data = get_option('wpsy_db_data');
					$wpsy_db_data = (is_array($wpsy_db_data)?$wpsy_db_data:array());
					
					$url = (isset($wpsy_db_data['wpsy_url'])?$wpsy_db_data['wpsy_url']:'');
					
					//pree($url);exit;
					if($url){
					
						$storefront_access_token = $wpsy_db_data['wpsy_storefront_token'];
						
						//pree($wpsy_db_data);exit;
						
						$curl = curl_init();
						
						$curl_data = array(
							CURLOPT_URL => 'https://'.$url.'/api/2024-10/graphql.json',
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_ENCODING => '',
							CURLOPT_MAXREDIRS => 10,
							CURLOPT_TIMEOUT => 0,
							CURLOPT_FOLLOWLOCATION => true,
							CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
							CURLOPT_CUSTOMREQUEST => 'POST',
							CURLOPT_POSTFIELDS =>trim($query),
							CURLOPT_HTTPHEADER => array(
								'Content-Type: application/graphql',
								'X-Shopify-Storefront-Access-Token: '.$storefront_access_token,
								'Cookie: _secure_session_id=45097142f4293cac96d24a74e547f039'
							),
						);
	
						curl_setopt_array($curl, $curl_data);
						
						$response = curl_exec($curl);
						
						//pree($curl_data);pree($response);exit;	
	
						curl_close($curl);
	
						if($inner){
							$data = json_decode($response);
							
							$data = (is_object($data) && property_exists($data, 'data')) ? $data->data : '';

	
							return $data;
						}else{
							echo $response;exit;
						}
				
					}
				}
			
			}
		}
	}