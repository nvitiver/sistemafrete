<?php

namespace App\Controller;


use App\Entity\Orders;
use App\Entity\Products;
use App\Entity\Quotations;
use App\Repository\OrdersRepository;
use App\Repository\ProductsRepository;
use App\Repository\QuotationsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CorreiosCalculatePortage;

/**
 * @Route("/api", name="api")
 */
class ApiController extends AbstractController
{

    ################################
    ###   PRODUCTS    BEGIN      ###
    ################################
    /**
     * @Route("/products", name="products_api_all", methods={"GET"})
     */
    public function products_all(ProductsRepository $productsRepository)
    {
        return $this->json([
            'data' => $productsRepository->findAll(),
        ]);
    }

    /**
     * @Route("/products/{id}", name="products_api_id", methods={"GET"})
     */
    public function products_show(ProductsRepository $productsRepository, int $id)
    {
        return $this->json([
            'data' => $productsRepository->findBy(['id' => $id]),
        ]);
    }

    /**
     * @Route("/products/create", name="products_api_create", methods={"POST"})
     */
    public function products_create(Request $resquet)
    {
        $data = $resquet->request->all();

        $product = new Products($data['name'],$data['weight'],$data['length'],$data['height'],$data['width']);

        $doctrine = $this->getDoctrine()->getManager();
        $doctrine->persist($product);
        $doctrine->flush();

        return $this->json([
            'data' => 'The product '. $data['name'] .' was created with success.'
        ]);
    }

    /**
     * @Route("/products/{id}", name="products_api_update", methods={"PUT", "PATCH"})
     */
    public function products_update(int $id, Request $resquet)
    {
        $data = $resquet->request->all();

        $product = $this->getDoctrine()->getRepository(Products::class)->find($id);

        $product->setName($data['name']);
        $product->setWeight($data['weight']);
        $product->setWeight($data['length']);
        $product->setWeight($data['height']);
        $product->setWeight($data['width']);

        $doctrine = $this->getDoctrine()->getManager();
        $doctrine->persist($product);
        $doctrine->flush();

        return $this->json([
            'data' => 'The product '. $data['name'] .' was updated with success.'
        ]);
    }

    ################################
    ###   PRODUCTS    END        ###
    ################################


    ################################
    ###   ORDERS    BEGIN        ###
    ################################
    /**
     * @Route("/orders", name="orders_api_all", methods={"GET"})
     */
    public function orders_all(OrdersRepository $OrdersRepository)
    {
        return $this->json([
            'data' => $OrdersRepository->findAll(),
        ]);
    }

    /**
     * @Route("/orders/{id}", name="orders_api_id", methods={"GET"})
     */
    public function orders_show(OrdersRepository $OrdersRepository, int $id)
    {
        return $this->json([
            'data' => $OrdersRepository->findBy(['id' => $id]),
        ]);
    }

    /**
     * @Route("/orders/create", name="orders_api_create", methods={"POST"})
     */
    public function orders_create(Request $resquet)
    {
        $data = $resquet->request->all();

        $orders = new Orders();
        $orders->setCepOrigin($data['cep_origin']);
        $orders->setCepDestiny($data['cep_destiny']);

        $product_decode = json_decode($data['products']);

        $order_product= new Products(
            $product_decode->name,
            $product_decode->weight,
            $product_decode->length,
            $product_decode->height,
            $product_decode->width
        );
        $orders->setProducts($order_product);

        $doctrine = $this->getDoctrine()->getManager();
        $doctrine->persist($orders);
        $doctrine->flush();

        return $this->json([
            'data' => 'The orders was created with success.'
        ]);
    }

    /**
     * @Route("/orders/{id}", name="orders_api_update", methods={"PUT", "PATCH"})
     */
    public function orders_update(int $id, Request $resquet)
    {
        $data = $resquet->request->all();

        $orders = $this->getDoctrine()->getRepository(Orders::class)->find($id);

        $orders->setCepOrigin($data['cep_origin']);
        $orders->setCepDestiny($data['cep_destiny']);

        $product_decode = json_decode($data['products']);
        $order_product= new Products(
            $product_decode->name,
            $product_decode->weight,
            $product_decode->length,
            $product_decode->height,
            $product_decode->width
        );
        $orders->setProducts($order_product);

        $doctrine = $this->getDoctrine()->getManager();
        $doctrine->persist($orders);
        $doctrine->flush();

        return $this->json([
            'data' => 'The orders was updated with success.'
        ]);
    }

    ################################
    ###   ORDERS    END          ###
    ################################

    ################################
    ###   QUOTATIONS    BEGIN    ###
    ################################
    /**
     * @Route("/quotations", name="quotations_api_all", methods={"GET"})
     */
    public function quotations_all(QuotationsRepository $quotationsRepository)
    {
        return $this->json([
            'data' => $quotationsRepository->findAll(),
        ]);
    }

    /**
     * @Route("/quotations/{id}", name="quotations_api_id", methods={"GET"})
     */
    public function quotations_show(QuotationsRepository $quotationsRepository, int $id)
    {
        return $this->json([
            'data' => $quotationsRepository->findBy(['id' => $id]),
        ]);
    }


    private $calculatePortage;

    function __construct(CorreiosCalculatePortage $calculatePortage)
    {
        $this->calculatePortage = $calculatePortage;
    }


    /**
     * @Route("/quotations/create", name="quotations_api_create", methods={"POST"})
     */
    public function quotations_create(Request $request)
    {
        $data = $request->request->all();


        ## STEP 1 Product
        $product_decode = json_decode($data['products']);

        $order_product= new Products(
            $product_decode->name,
            $product_decode->weight,
            $product_decode->length,
            $product_decode->height,
            $product_decode->width
        );


        ## STEP 2 Order
        $order_decode = json_decode($data['orders']);

        $quotation_order= new Orders();
        $quotation_order->setCepOrigin($order_decode->cepOrigin);
        $quotation_order->setCepDestiny($order_decode->cepDestiny);
        $quotation_order->setProducts($order_product);


        ## STEP 3 Quotation
        $quotation = new Quotations();
        $quotation->setServiceCode($data['service_code']);
        $quotation->setOrders($quotation_order);

        $result = $this->calculatePortage->calculate(
            $quotation->getOrders()->getCepOrigin(),
            $quotation->getOrders()->getCepDestiny(),
            $quotation->getOrders()->getProducts()->getWeight(),
            $quotation->getOrders()->getProducts()->getWidth(),
            $quotation->getOrders()->getProducts()->getLength(),
            $quotation->getOrders()->getProducts()->getHeight(),
            $quotation->getServiceCode()
        );

        $quotation->setPortageValue($result[0]);
        $quotation->setDeadline($result[1]);


        $doctrine = $this->getDoctrine()->getManager();
        $doctrine->persist($quotation);
        $doctrine->flush();

        return $this->json([
            'data' => 'The quotation was created with success.'
        ]);
    }

    /**
     * @Route("/quotations/{id}", name="quotations_api_update", methods={"PUT", "PATCH"})
     */
    public function quotations_update(int $id, Request $resquet)
    {
        $data = $resquet->request->all();


        ## STEP 1 Product
        $product_decode = json_decode($data['products']);

        $order_product= new Products(
            $product_decode->name,
            $product_decode->weight,
            $product_decode->length,
            $product_decode->height,
            $product_decode->width
        );


        ## STEP 2 Order
        $order_decode = json_decode($data['orders']);

        $quotation_order= new Orders();
        $quotation_order->setCepOrigin($order_decode->cepOrigin);
        $quotation_order->setCepDestiny($order_decode->cepDestiny);
        $quotation_order->setProducts($order_product);


        ## STEP 3 Quotation
        $quotation = $this->getDoctrine()->getRepository(Quotations::class)->find($id);
        $quotation->setServiceCode($data['service_code']);
        $quotation->setOrders($quotation_order);

        $result = $this->calculatePortage->calculate(
            $quotation->getOrders()->getCepOrigin(),
            $quotation->getOrders()->getCepDestiny(),
            $quotation->getOrders()->getProducts()->getWeight(),
            $quotation->getOrders()->getProducts()->getWidth(),
            $quotation->getOrders()->getProducts()->getLength(),
            $quotation->getOrders()->getProducts()->getHeight(),
            $quotation->getServiceCode()
        );

        $quotation->setPortageValue($result[0]);
        $quotation->setDeadline($result[1]);


        $doctrine = $this->getDoctrine()->getManager();
        $doctrine->persist($quotation);
        $doctrine->flush();

        return $this->json([
            'data' => 'The quotation was updated with success.'
        ]);
    }

    ################################
    ###   QUOTATIONS    END      ###
    ################################
}

