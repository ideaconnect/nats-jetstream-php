<?php

namespace Basis\Nats\Service;

use Basis\Nats\Client;
use Basis\Nats\Message\Payload;
use Basis\Nats\Queue;

class ServiceEndpoint
{
    private int $num_requests = 1;

    private int $num_errors = 0;

    private string $last_error = '';

    private float $processing_time = 0;

    private Client|Queue $subscription;

    public function __construct(
        private readonly Service         $service,
        private readonly string          $name,
        private readonly string          $subject,
        private readonly EndpointHandler $endpointHandler,
        private readonly string          $queue_group = 'q'
    )
    {
        $this->subscription = $this->service->client->subscribeQueue(
            $this->subject,
            $this->queue_group,
            function (Payload $message) {
                // Start calculating the time
                $start = microtime(true);

                // Update the endpoint metrics
                $this->num_requests += 1;

                // Run the handler
                $response = $this->endpointHandler->handle($message);

                // Add to the total processing time
                $this->processing_time += microtime(true) - $start;

                // Return the array
                return $response;
            }
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getQueueGroup(): string
    {
        return $this->queue_group;
    }

    public function getAverageProcessingTime(): float
    {
        return round($this->getProcessingTime() / $this->getNumRequests());
    }

    public function getLastError(): string
    {
        return $this->last_error;
    }

    public function getNumErrors(): int
    {
        return $this->num_errors;
    }

    public function getNumRequests(): int
    {
        return $this->num_requests;
    }

    public function getProcessingTime(): float
    {
        return $this->processing_time * 1e9;
    }

    public function resetStats(): void
    {
        $this->num_requests = 1;
        $this->num_errors = 0;
        $this->processing_time = 0;
        $this->last_error = '';
    }
}