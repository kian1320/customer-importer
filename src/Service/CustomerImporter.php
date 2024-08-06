<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Customer;

class CustomerImporter
{
    private $client;
    private $em;

    public function __construct(HttpClientInterface $client, EntityManagerInterface $em)
    {
        $this->client = $client;
        $this->em = $em;
    }

    public function importCustomers($nationality = 'AU', $results = 100)
{
    $response = $this->client->request('GET', 'https://randomuser.me/api', [
        'query' => [
            'nat' => $nationality,
            'results' => $results,
        ],
    ]);

    $data = $response->toArray();
    
    foreach ($data['results'] as $userData) {
        // Log user data for debugging
        error_log(print_r($userData, true));

        $email = $userData['email'];
        $customer = $this->em->getRepository(Customer::class)->findOneBy(['email' => $email]);

        if (!$customer) {
            $customer = new Customer();
        }

        $customer->setFirstName($userData['name']['first']);
        $customer->setLastName($userData['name']['last']);
        $customer->setEmail($email);
        $customer->setUsername($userData['login']['username']);
        $customer->setGender($userData['gender']);
        $customer->setCountry($userData['location']['country']);
        $customer->setCity($userData['location']['city']);
        $customer->setPhone($userData['phone']);
        $customer->setPassword(md5($userData['login']['password']));

        $this->em->persist($customer);
    }

    $this->em->flush();
}

}
