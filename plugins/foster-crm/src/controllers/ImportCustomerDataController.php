<?php

namespace foster\fostercrm\controllers;

use Craft;
use craft\web\Controller;
use League\Csv\Reader;
use yii\web\Response;

class ImportCustomerDataController extends Controller
{
    protected array|int|bool $allowAnonymous = false;

    /**
     * Import customers from customers.csv (Web and CLI)
     */
    public function actionImportCustomers(): Response|string
    {
        $filePath = Craft::$app->getPath()->getStoragePath() . '/csv/customers.csv';

        if (!file_exists($filePath)) {
            $message = "Error: customers.csv file not found!";
            return $this->respond($message, false);
        }

        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        foreach ($records as $row) {
            $data = $this->validateAndSanitizeCustomerData($row);
            if (!$data) {
                $this->log("Skipping invalid record: " . implode(', ', $row));
                continue;
            }

            Craft::$app->db->createCommand()
                ->insert('{{%customers}}', $data)
                ->execute();
        }

        return $this->respond("Customer data imported successfully!", true);
    }

    /**
     * Import purchases from purchase_history.csv (Web and CLI)
     */
    public function actionImportPurchases(): Response|string
    {
        $filePath = Craft::$app->getPath()->getStoragePath() . '/csv/purchase_history.csv';

        if (!file_exists($filePath)) {
            $message = "Error: purchase_history.csv file not found!";
            return $this->respond($message, false);
        }

        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        foreach ($records as $row) {
            $customer = (new \yii\db\Query())
                ->select(['id'])
                ->from('{{%customers}}')
                ->where(['email' => strtolower($row['customer_email'])])
                ->one();

            if (!$customer) {
                $this->log("Skipping record with unknown customer email: {$row['customer_email']}");
                continue;
            }

            Craft::$app->db->createCommand()
                ->insert('{{%purchase_history}}', [
                    'customer_id' => $customer['id'],
                    'purchasable' => $row['purchasable'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity'],
                    'total' => $row['total'],
                    'purchase_date' => $row['purchase_date'],
                ])
                ->execute();
        }

        return $this->respond("Purchase history imported successfully!", true);
    }

    /**
     * Display customers and their latest purchase in a Twig template
     */
    public function actionIndex(): Response
    {
        // Query to fetch customers and their latest purchase
        $customers = (new \yii\db\Query())
            ->select([
                'customers.id',
                'customers.name',
                'customers.email',
                'customers.phone_number',
                'customers.loyalty_points',
                'p.purchase_date',
                'p.total',
            ])
            ->from('{{%customers}} customers')
            ->leftJoin('{{%purchase_history}} p', 'p.customer_id = customers.id')
            ->groupBy(['customers.id'])
            ->orderBy(['p.purchase_date' => SORT_DESC])
            ->all();

        // Render the template with the data
        return $this->renderTemplate('fostercrm/index', ['customers' => $customers]);
    }

    /**
     * Validate and sanitize customer data
     */
    private function validateAndSanitizeCustomerData(array $data): ?array
    {
        if (!preg_match('/^[a-zA-Z\s]+$/', $data['name']) ||
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
            !preg_match('/^\d{3}-\d{3}-\d{4}$/', $data['phone_number']) ||
            !\DateTime::createFromFormat('Y-m-d', $data['created_at'])
        ) {
            return null;
        }

        return [
            'name' => ucfirst(strtolower($data['name'])),
            'email' => strtolower($data['email']),
            'phone_number' => $data['phone_number'],
            'created_at' => $data['created_at'],
            'loyalty_points' => 0,
        ];
    }

    /**
     * Log or display a message based on the request type
     */
    private function log(string $message): void
    {
        if (Craft::$app->request->getIsConsoleRequest()) {
            echo $message . PHP_EOL;
        } else {
            Craft::info($message, __METHOD__);
        }
    }

    /**
     * Respond based on the request type
     */
    private function respond(string $message, bool $success): Response|string
    {
        if (Craft::$app->request->getIsConsoleRequest()) {
            echo $message . PHP_EOL;
            return $success ? 0 : 1;
        }

        return $this->asJson(['success' => $success, 'message' => $message]);
    }
}
