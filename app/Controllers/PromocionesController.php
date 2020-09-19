<?php

namespace App\Controllers;

use Slim\Views\Twig as View;
use App\Models\User;
use App\Models\Individuo;



class CRMController extends Controller {
	
	public function index($request, $response, array $args) {
	}

	public function facebook_respondedor($request, $response, array $args) 
	{
		return $this->container->view->render($response, 'admin_views/crm/facebook_responder.twig', [
		]);
		
	}

	public function facebook_respondedor_post($request, $response, array $args) 
	{
		$token = 'EAACEdEose0cBAFG0c81QfK5lvcjoC4KIh7L8YubwgPfXZBed4dAq1s2NLZC4W9EyOgPm7sFi9wNy2IijZBPv5Hibppvo2e7dJHwwZBg3iZBidvD1h3RBYQMi6ZCbxMjxUZBwHUfZBx9UyQSVOtLqZB27AV8YaqAgK4GufSdg8lmoyxRtqaSWgRLmV2eh2l2sPrVISr5rGIs2fIgZDZD';
		$link = 'https://graph.facebook.com/v3.0/me/posts/?fields=id&limit=100&access_token='.$token;

		$client = new \GuzzleHttp\Client();
		$res = $client->request('GET', $link, [
		    'form_params' => [
		    ]
		]);
		$string = $res->getBody()->getContents();
		$posts = json_decode($string, true);

		$total_posts = array();

		// cada post:
		$parar = 0;
		while($parar == 0)
		{
			for($i = 0; $i < count($posts['data']); $i++)
			{
				array_push($total_posts, $posts['data'][$i]['id']);
			}
			if(isset($posts['paging']['next']))
			{
				$res = $client->request('GET', $posts['paging']['next'], []);
				$string = $res->getBody()->getContents();
				$posts = json_decode($string, true);
			}
			else
			{
				$parar = 1;
			}
		}


		
		for($i = 0; $i < count($total_posts); $i++)
		{	
			print $total_posts[$i]."<br>";
		}
		return 1;
	}

}