<?php

namespace Drupal\os2web_audit\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\advancedqueue\Job;
use Drush\Attributes\Argument;
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
   * Retries failed jobs in the os2web_audit queue.
   */
  #[Command(name: 'audit:retry-failed-jobs')]
  #[Argument(name: 'limit', description: "The number of jobs to retry. Minimum: 1, Maximum: 5000")]
  public function retryJobs(int $limit = 1000): void {

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
  #[Command(name: 'audit:retry-job')]
  #[Argument(name: 'id', description: "The job ID to retry.")]
  #[Option(name: 'ignore-state', description: 'Retries job regardless of state.')]
  public function retryJob(int $id, bool $ignoreState): void {

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
