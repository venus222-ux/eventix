<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class CheckConnectionsCommand extends Command
{
    /**
     * Numele și sigiliul comenzii Artisan.
     */
    protected $signature = 'check:connections';

    /**
     * Descrierea comenzii.
     */
    protected $description = 'Verifică conexiunea și latența pentru MySQL, MongoDB, Redis și Elasticsearch';

    public function handle(): void
    {
        $this->info('🔍 Verificare conexiuni servicii...');
        $this->newLine();

        $results = [
            $this->checkMysql(),
            $this->checkMongo(),
            $this->checkRedis(),
            $this->checkElasticsearch(),
        ];

        $this->table(['Serviciu', 'Host Target', 'Status', 'Latență / Detalii'], $results);
    }

    private function checkMysql(): array
    {
        $host = config('database.connections.mysql.host', env('DB_HOST'));
        $start = microtime(true);

        try {
            DB::connection('mysql')->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2) . ' ms';
            return ['MySQL', $host, '✅ Conectat', $latency];
        } catch (Exception $e) {
            return ['MySQL', $host, '❌ Eroare', $this->formatError($e->getMessage())];
        }
    }

    private function checkMongo(): array
    {
        $host = env('DB_MONGO_HOST', env('MONGODB_HOST', '127.0.0.1'));
        $start = microtime(true);

        try {
            // Trimite o comandă de ping către serverul MongoDB
            DB::connection('mongodb')->getMongoClient()->listDatabases();
            $latency = round((microtime(true) - $start) * 1000, 2) . ' ms';
            return ['MongoDB', $host, '✅ Conectat', $latency];
        } catch (Exception $e) {
            return ['MongoDB', $host, '❌ Eroare', $this->formatError($e->getMessage())];
        }
    }

    private function checkRedis(): array
    {
        $host = config('database.redis.default.host', env('REDIS_HOST'));
        $start = microtime(true);

        try {
            Redis::connection()->ping();
            $latency = round((microtime(true) - $start) * 1000, 2) . ' ms';
            return ['Redis', $host, '✅ Conectat', $latency];
        } catch (Exception $e) {
            return ['Redis', $host, '❌ Eroare', $this->formatError($e->getMessage())];
        }
    }

    private function checkElasticsearch(): array
    {
        $host = env('ELASTICSEARCH_HOST', 'boilerplate_elasticsearch');
        $port = env('ELASTICSEARCH_PORT', '9200');
        $url = "http://{$host}:{$port}";
        $start = microtime(true);

        try {
            $response = Http::timeout(3)->get($url);

            if ($response->successful()) {
                $latency = round((microtime(true) - $start) * 1000, 2) . ' ms';
                return ['Elasticsearch', "{$host}:{$port}", '✅ Conectat', $latency];
            }

            return ['Elasticsearch', "{$host}:{$port}", '❌ Eroare', 'HTTP Status ' . $response->status()];
        } catch (Exception $e) {
            return ['Elasticsearch', "{$host}:{$port}", '❌ Eroare', 'Offline / Timeout'];
        }
    }

    private function formatError(string $message): string
    {
        return strlen($message) > 40 ? substr($message, 0, 37) . '...' : $message;
    }
}
