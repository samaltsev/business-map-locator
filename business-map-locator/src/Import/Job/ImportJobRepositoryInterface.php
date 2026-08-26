<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Job;
use BusinessMapLocator\Import\Dto\ImportJob;
interface ImportJobRepositoryInterface {
    public function create(string $token, ImportJob $job): ImportJob;
    public function findByToken(string $token): ?ImportJob;
    public function find(string $token): ?ImportJob;
    public function claim(string $token,string $lockedBy,int $ttl): ?ImportJob;
    public function acquireLease(string $token,string $lockedBy,int $ttl): ?ImportJob;
    public function releaseLease(string $token,string $lockedBy): void;
    public function save(string $token,ImportJob $job,int $expectedVersion): ?ImportJob;
    public function updateAtomic(string $token,ImportJob $job,int $expectedVersion): ?ImportJob;
    public function cancel(string $token,ImportJob $job,int $expectedVersion): ?ImportJob;
    /** @return ImportJob[] */ public function findExpired(int $limit=100): array;
    /** @return ImportJob[] */ public function expired(int $limit=100): array;
    public function deleteExpired(int $limit=100): int;
    public function deleteById(int $id): void;
    public function activeFilePaths(): array;
    /** @return ImportJob[] */ public function listByOwner(int $ownerUserId,int $limit=50,int $offset=0): array;
}
