<?php

namespace Tests\Feature\HR\EmployeeApplications;

use App\Modules\HR\EmployeeApplications\Checker\MonthlyPendingApplicationChecker;
use App\Modules\HR\EmployeeApplications\Checker\Queries\PendingApplicationQuery;
use App\Modules\HR\EmployeeApplications\Checker\DTOs\CheckerFilterDTO;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Tests\TestCase;

/**
 * Unit test for MonthlyPendingApplicationChecker.
 * 
 * This test focuses on verifying logic and interaction (Verification) 
 * without creating records in the database (No DB side-effects).
 */
class MonthlyPendingApplicationCheckerTest extends TestCase
{
    /** @test */
    public function it_verifies_existence_using_the_query_builder()
    {
        // 1. Arrange: Mock the dependencies
        $queryMock = Mockery::mock(PendingApplicationQuery::class);
        $eloquentBuilderMock = Mockery::mock(Builder::class);

        // Expect the query to be built with a CheckerFilterDTO
        $queryMock->shouldReceive('build')
            ->once()
            ->with(Mockery::type(CheckerFilterDTO::class))
            ->andReturn($eloquentBuilderMock);

        // Expect the exists() method to be called and return true
        $eloquentBuilderMock->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $checker = new MonthlyPendingApplicationChecker($queryMock);

        // 2. Act: Call the check method
        $result = $checker->check([
            'year' => 2026,
            'month' => 5,
            'branch_id' => 1
        ]);

        // 3. Assert: Verify the result is true
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_no_applications_exist()
    {
        // 1. Arrange
        $queryMock = Mockery::mock(PendingApplicationQuery::class);
        $eloquentBuilderMock = Mockery::mock(Builder::class);

        $queryMock->shouldReceive('build')->andReturn($eloquentBuilderMock);
        $eloquentBuilderMock->shouldReceive('exists')->andReturn(false);

        $checker = new MonthlyPendingApplicationChecker($queryMock);

        // 2. Act
        $result = $checker->check(['year' => 2026, 'month' => 5]);

        // 3. Assert
        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
