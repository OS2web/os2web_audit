<?php

namespace Drupal\os2web_audit\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\advancedqueue\Job;
use Drush\Attributes\Command;
use Drush\Attributes\Option;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple command to send log message into audit log.
 */
class RetryFailedQueueCommand extends DrushCommands {

  private const OS2WEB_AUDIT_QUEUE_ID = 'os2web_audit';
  private const ADVANCEDQUEUE_TABLE = 'advancedqueue';

  /**
   * Commands constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(
    protected Connection $connection,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database'),
    );
  }

  /**
   * Retries all failed jobs in the os2web_audit queue.
   *
   * @param array<string, mixed> $options
   *   The options array.
   */
  #[Command(name: 'audit:retry-failed-jobs')]
  #[Option(name: 'id', description: "Retry a specific job by ID (e.g. 1245.)")]
  #[Option(name: 'ignore-state', description: 'Retries job regardless of state. This only effects the --id option.')]
  #[Option(name: 'limit', description: "Retry (up to) a limited number of jobs. Minimum: 1, Maximum: 5000, Default 1000.")]
  public function retryFailedJobs($options = ['id' => NULL, 'ignore-state' => FALSE, 'limit' => NULL]): void {

    if (TRUE === $options['id']) {
      $this->writeln('Please specify a job ID, e.g. --id=1245.');
      return;
    }
    elseif (is_string($options['id'])) {
      $this->retryJob((int) $options['id'], $options['ignore-state']);
      return;
    }

    if (TRUE === $options['limit']) {
      // We use the default 1000.
      $this->retryJobs(1000);
      return;
    }
    elseif (is_string($options['limit'])) {
      $this->retryJobs((int) $options['limit']);
      return;
    }

    $this->retryAllFailedJobs();

  }

  /**
   * Retries all failed jobs in os2web_audit.
   */
  private function retryAllFailedJobs(): void {
    try {
      $this->connection->update('advancedqueue')
        ->fields(['state' => Job::STATE_QUEUED])
        ->condition('queue_id', 'os2web_audit')
        ->condition('state', Job::STATE_FAILURE)
        ->execute();

      $this->output()->writeln('Successfully retried all failed jobs.');
    }
    catch (\Exception $e) {
      $this->output()->writeln($e->getMessage());
    }

  }

  /**
   * Retries jobs in the os2web_audit queue.
   */
  private function retryJobs(int $limit): void {
    if ($limit < 1 || $limit > 5000) {
      $this->output()->writeln('Limit should be an integer between 1 and 5000.');
      return;
    }

    try {
      $ids = $this->connection->select(self::ADVANCEDQUEUE_TABLE, 'a')
        ->fields('a', ['job_id'])
        ->condition('queue_id', self::OS2WEB_AUDIT_QUEUE_ID)
        ->condition('state', Job::STATE_FAILURE)
        ->range(0, $limit)
        ->execute()
        ->fetchCol();

      $this->connection->update(self::ADVANCEDQUEUE_TABLE)
        ->fields(['state' => Job::STATE_QUEUED])
        ->condition('queue_id', self::OS2WEB_AUDIT_QUEUE_ID)
        ->condition('state', Job::STATE_FAILURE)
        ->condition('job_id', $ids, 'IN')
        ->execute();

      $this->output()->writeln('Successfully retried failed jobs.');
    }
    catch (\Exception $e) {
      $this->output()->writeln($e->getMessage());
    }
  }

  /**
   * Retries failed job in the os2web_audit queue.
   */
  private function retryJob(int $id, bool $ignoreState): void {

    try {
      // Check that job exists by fetching its state.
      $query = $this->connection->select(self::ADVANCEDQUEUE_TABLE, 'a')
        ->fields('a', ['state'])
        ->condition('job_id', $id);

      $result = $query->execute()->fetchAssoc();

      if (!$result) {
        $this->output()->writeln('Job not found.');
        return;
      }

      // State check.
      if (!$ignoreState && $result['state'] !== Job::STATE_FAILURE) {
        $this->output()->writeln('Job is not in a failed state.');
        return;
      }

      $query = $this->connection->update(self::ADVANCEDQUEUE_TABLE)
        ->fields(['state' => Job::STATE_QUEUED])
        ->condition('queue_id', self::OS2WEB_AUDIT_QUEUE_ID)
        ->condition('job_id', $id);

      if (!$ignoreState) {
        $query->condition('state', Job::STATE_FAILURE);
      }

      $result = $query->execute();

      if ($result) {
        $this->output()->writeln('Successfully retried job.');
      }
      else {
        $this->output()->writeln('Failed retrying job.');
      }

    }
    catch (\Exception $e) {
      $this->output()->writeln($e->getMessage());
    }
  }

}
