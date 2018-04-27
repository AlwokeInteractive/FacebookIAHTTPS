<?php

  // Alwoke minimal FB API
	class AlwokeFB
	{
		private $appID="";
		private $appSecret="";
		private $token=null;
		
		/**
		 * Construct Facebook with AppID and AppSecret
		 * @param string $appID
		 * @param string $appSecret
		 */
		public function __construct($appID,$appSecret)
		{
			$this->appID=$appID;
			$this->appSecret=$appSecret;
		}
		
		/**
		 * Returns the AppToken
		 * @return string AppToken
		 */
		public function GetAppToken()
		{
			return $this->appID."|".$this->appSecret;	
		}
		
		/**
		 * Set a Token to overwrite to AppToken in case you need more Permissions
		 * @param string $token
		 */
		public function SetToken($token)
		{
			$this->token=$token;
		}
		
		/**
		 * Returns either the set Token or AppToken as Fallback
		 * @return string - The Token
		 */
		public function GetToken()
		{
			if (isset($this->token))
			{
				return $this->token;
			}
			else
			{
				return $this->GetAppToken();
			}
		}
		
		private $proxyType=CURLPROXY_HTTP;
		private $proxyHost="";
		private $proxyAuth="";
		public function SetProxy($host,$type=CURLPROXY_HTTP,$auth="")
		{
			$this->proxyType=$type;
			$this->proxyHost=$host;
			$this->proxyAuth=$auth;
		}
		
		/**
		 * Call Facebook API mit newest Version
		 * @param string $url - The Relative URL (Starts with /)
		 * @param string $method - POST,GET,PUT
		 * @throws Exception - If a CURL Error occured!
		 */
		public function API($url,$method="GET",$params=array(),$raw=false)
		{
			// Parse Parameters
			$params["method"]=$method;
			$params["access_token"]=$this->GetToken();
			
			$curl=curl_init();
			$options=array(CURLOPT_URL=>(($raw)?"":"https://graph.facebook.com").$url,CURLOPT_POSTFIELDS=>http_build_query($params,null,'&'),CURLOPT_CUSTOMREQUEST=>"POST",CURLOPT_RETURNTRANSFER=>true,CURLOPT_ENCODING=>"",CURLOPT_MAXREDIRS=>10,CURLOPT_TIMEOUT=>30,CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,CURLOPT_PROXYTYPE=>$this->proxyType,CURLOPT_PROXY=>$this->proxyHost,CURLOPT_PROXYAUTH=>$this->proxyAuth);
			curl_setopt_array($curl,$options);
			$text=curl_exec($curl);
			$error=curl_error($curl);
			if ($error=="")
			{
				if (preg_match("/oauth/",$url))
				{
					$text=str_replace('=','":"',$text);
					$text=str_replace('&','","',$text);
					return json_decode('{"'.$text.'"}');
				}
				else
				{
					return json_decode($text);
				}
			}
			else
			{
				throw new Exception($error);
			}
		}
	}
	
	$fb=new AlwokeFB("","");
  
  // INSERT A PAGE ACCESS TOKEN HERE! You can get it here https://developers.facebook.com/tools/explorer/
	$fb->SetToken("");
	
	// Create Array that holds the Articles
	$articleList=array();
	
	// Fetch all Articles from Facebook
	$data=null;
	$page=null;
	while (true)
	{
		if ($page===null)
		{
			$data=$fb->API("/v2.10/me/instant_articles?fields=canonical_url,html_source");
		}
		else
		{
			$data=$fb->API("/v2.10/me/instant_articles?fields=canonical_url,html_source&after=".$page);
		}
		
		// Add all Articles to List
		if (isset($data->data) && count($data->data)>0)
		{
			foreach ($data->data as $ia)
			{
				if (isset($ia->canonical_url))
				{
					echo "Adding Article to List ".$ia->canonical_url."\n";				
					$articleList[]=$ia;
				}
			}
		}
		else
		{
			// No more results
			break;
		}
		
		// Check for next Page
		if (isset($data->paging->cursors->after))
		{
			$page=$data->paging->cursors->after;
		}
		else
		{
			break;
		}
	}
	
	function canonical_exists(&$list,$can)
	{
		foreach ($list as $a)
		{
			if ($a->canonical_url==$can)
			{
				return true;
			}
		}
		return false;
	}
	
	// Find all Articles without https
	foreach ($articleList as $article)
	{
		echo "Checking ".$article->canonical_url."...\n";
		if (preg_match('/http\:\/\//',$article->canonical_url))
		{
			echo "Article is only HTTP...\n";
			
			// Article is HTML, look for HTTPS of same Article
			$https_canonical=str_replace("http:","https:",$article->canonical_url);
			
			// Check if HTTPS Version exists already
			if (canonical_exists($articleList,$https_canonical))
			{
				echo " - Found HTTPS Version, doing nothing!\n";
			}
			else
			{
				echo " - There is no HTTP Version, creating!\n";
				
				// Converting Canonical to HTTPS
				$source=$article->html_source;
				$source=str_replace($article->canonical_url,$https_canonical,$source);
				
				// Creating Article on Facebook
				$data=$fb->API("/v2.10/me/instant_articles","POST",array("published"=>true,"html_source"=>$source));
				print_r($data);
			}
		}
	}
	

	
?>
