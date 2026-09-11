<?php

declare(strict_types=1);

namespace Codeception\Lib;

use Tests\Support\CodeTester;
use Codeception\Exception\ConfigurationException;
use Codeception\Stub;
use Codeception\Test\Loader\Gherkin as GherkinLoader;
use Codeception\Test\Metadata;
use Codeception\Test\TestCaseWrapper;
use Codeception\Test\Unit;
use Codeception\Util\ReflectionHelper;
use PHPUnit\Framework\TestCase;

class GroupManagerTest extends \Codeception\Test\Unit
{
    protected CodeTester $tester;

    protected GroupManager $manager;

    public function testGroupsFromArray()
    {
        $this->manager = new GroupManager(['important' => ['tests/data/group_manager_test/UserTest.php:testName', 'tests/data/group_manager_test/PostTest.php']]);
        $test1 = $this->makeTestCase('tests/data/group_manager_test/UserTest.php', 'testName');
        $test2 = $this->makeTestCase('tests/data/group_manager_test/PostTest.php');
        $test3 = $this->makeTestCase('UserTest.php', 'testNot');
        $this->assertContains('important', $this->manager->groupsForTest($test1));
        $this->assertContains('important', $this->manager->groupsForTest($test2));
        $this->assertNotContains('important', $this->manager->groupsForTest($test3));
    }

    public function testRealPathForFileWithMethodName()
    {
        $this->manager = new GroupManager(['important' => ['tests/data/group_manager_test/PostTest.php:testName']]);
        $test = $this->makeTestCase('tests/data/group_manager_test/PostTest.php', 'testName');
        $this->assertContains('important', $this->manager->groupsForTest($test));
    }

    public function testGroupsFromFile()
    {
        $this->manager = new GroupManager(['important' => 'tests/data/group_manager_test/test_groups']);
        $test1 = $this->makeTestCase('tests/data/group_manager_test//UserTest.php', 'testName');
        $test2 = $this->makeTestCase('tests/data/group_manager_test//PostTest.php');
        $test3 = $this->makeTestCase('tests/data/group_manager_test//UserTest.php', 'testNot');
        $this->assertContains('important', $this->manager->groupsForTest($test1));
        $this->assertContains('important', $this->manager->groupsForTest($test2));
        $this->assertNotContains('important', $this->manager->groupsForTest($test3));
    }

    public function testGroupWithRelativePathsFromFile()
    {
        $this->manager = new GroupManager(['important' => 'tests/data/group_manager_test/relative_paths']);
        $test1 = $this->makeTestCase('tests/data/group_manager_test/UserTest.php', 'testName');
        $test2 = $this->makeTestCase('tests/data/group_manager_test/PostTest.php');
        $test3 = $this->makeTestCase('tests/data/group_manager_test/UserTest.php', 'testNot');
        $this->assertContains('important', $this->manager->groupsForTest($test1));
        $this->assertContains('important', $this->manager->groupsForTest($test2));
        $this->assertNotContains('important', $this->manager->groupsForTest($test3));
    }

    public function testGroupsFromFileOnWindows()
    {
        $this->manager = new GroupManager(['important' => 'tests//data/group_manager_test/group_3']);
        $test = $this->makeTestCase('tests/data/group_manager_test/WinTest.php');
        $this->assertContains('important', $this->manager->groupsForTest($test));
    }

    public function testGroupsFromArrayOnWindows()
    {
        $this->manager = new GroupManager(['important' => ['tests\data\group_manager_test\WinTest.php']]);
        $test = $this->makeTestCase('tests/data/group_manager_test/WinTest.php');
        $this->assertContains('important', $this->manager->groupsForTest($test));
    }

    public function testGroupsByPattern()
    {
        $this->manager = new GroupManager(['group_*' => 'tests/data/group_manager_test/group_*']);
        $test1 = $this->makeTestCase('tests/data/group_manager_test/UserTest.php');
        $test2 = $this->makeTestCase('tests/data/group_manager_test/PostTest.php');
        $this->assertContains('group_1', $this->manager->groupsForTest($test1));
        $this->assertContains('group_2', $this->manager->groupsForTest($test2));
    }


    public function testGroupsByPatternWithMultipleDigits()
    {
        $this->manager = new GroupManager(['group_chunk_*' => 'tests/data/group_manager_test/group_chunk_*']);
        $test1 = $this->makeTestCase('tests/data/group_manager_test/UserTest.php');
        $test2 = $this->makeTestCase('tests/data/group_manager_test/PostTest.php');

        $this->assertContains('group_chunk_1_1', $this->manager->groupsForTest($test1));
        $this->assertContains('group_chunk_1_2', $this->manager->groupsForTest($test2));
    }

    public function testGroupsByDifferentPattern()
    {
        $this->manager = new GroupManager(['g_*' => 'tests/data/group_manager_test/group_*']);
        $test1 = $this->makeTestCase('tests/data/group_manager_test/UserTest.php');
        $test2 = $this->makeTestCase('tests/data/group_manager_test/PostTest.php');

        $this->assertContains('g_1', $this->manager->groupsForTest($test1));
        $this->assertContains('g_2', $this->manager->groupsForTest($test2));
    }

    public function testGroupsFileHandlesWhitespace()
    {
        $this->manager = new GroupManager(['whitespace_group_test' => 'tests/data/group_manager_test/whitespace_group_test']);
        $goodTest = $this->makeTestCase('tests/data/group_manager_test/UserTest.php');
        $badTest = $this->makeTestCase('');

        $this->assertContains('whitespace_group_test', $this->manager->groupsForTest($goodTest));
        $this->assertEmpty($this->manager->groupsForTest($badTest));
    }

    public function testLoadSpecificScenarioFromFile()
    {
        $this->manager = new GroupManager(['gherkinGroup1' => 'tests/data/group_manager_test/gherkinGroup1']);
        $loader = new GherkinLoader();
        $loader->loadTests(codecept_absolute_path('tests/data/refund.feature'));

        $test = $loader->getTests()[0];
        $this->assertContains('gherkinGroup1', $this->manager->groupsForTest($test));
    }

    public function testLoadSpecificScenarioWithMultibyteStringFromFile()
    {
        $this->manager = new GroupManager(['gherkinGroup2' => 'tests/data/group_manager_test/gherkinGroup2']);
        $loader = new GherkinLoader();
        $loader->loadTests(codecept_absolute_path('tests/data/refund2.feature'));

        $test = $loader->getTests()[0];
        $this->assertContains('gherkinGroup2', $this->manager->groupsForTest($test));
    }

    public function testThrowsExceptionIfDirectoryDoesNotExists()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('tests/data/missing-directory');
        $this->expectExceptionMessage('does not exist');
        new GroupManager(['invalidGroup' => ['tests/data/missing-directory']]);
    }

    public function testThrowsExceptionIfDirectoryDoesNotExistsWithColonAndTestName()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('tests/data/missing-directory');
        $this->expectExceptionMessage('does not exist');
        new GroupManager(['invalidGroup' => ['tests/data/missing-directory:testName']]);
    }

    public function testThrowsExceptionIfDirectoryInGroupFileDoesNotExists()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('tests/data/missing-directory');
        $this->expectExceptionMessage('does not exist');
        new GroupManager(['important' => 'tests/data/group_manager_test/missing_directory']);
    }

    public function testGroupsForIntegerKeyedExamples()
    {
        $file = 'tests/data/group_manager_test/UserTest.php';
        $this->manager = new GroupManager([
            'first' => [$file . ':testName#0'],
            'second' => [$file . ':testName#1'],
            'tenth' => [$file . ':testName#10'],
            'whole' => [$file . ':testName'],
        ]);
        $example0 = $this->makeExample($file, 'testName', 0, 0);
        $example1 = $this->makeExample($file, 'testName', 1, 1);
        $example10 = $this->makeExample($file, 'testName', 10, 10);

        $this->assertSame(['first', 'whole'], $this->manager->groupsForTest($example0));
        $this->assertSame(['second', 'whole'], $this->manager->groupsForTest($example1));
        // "#1" must not match example "#10" as a prefix
        $this->assertSame(['tenth', 'whole'], $this->manager->groupsForTest($example10));
    }

    public function testGroupsForStringKeyedExamples()
    {
        $file = 'tests/data/group_manager_test/UserTest.php';
        $this->manager = new GroupManager([
            'by_key' => [$file . ':testName@head manager'],
            'by_position' => [$file . ':testName#1'],
            'other_position' => [$file . ':testName#0'],
            'whole' => [$file . ':testName'],
        ]);
        // second example of a data provider with string keys
        $example = $this->makeExample($file, 'testName', 'head manager', 1);

        $this->assertSame(['by_key', 'by_position', 'whole'], $this->manager->groupsForTest($example));
    }

    public function testExampleSuffixDoesNotMatchOtherTests()
    {
        $file = 'tests/data/group_manager_test/UserTest.php';
        $this->manager = new GroupManager(['important' => [$file . ':testName#0']]);

        $this->assertNotContains('important', $this->manager->groupsForTest($this->makeTestCase($file, 'testName')));
        $this->assertNotContains('important', $this->manager->groupsForTest($this->makeExample($file, 'testNameLong', 0, 0)));
        $this->assertNotContains('important', $this->manager->groupsForTest($this->makeExample($file, 'testName', 'key', 2)));
    }

    public function testPlainNameStillMatchesAsPrefix()
    {
        $file = 'tests/data/group_manager_test/UserTest.php';
        $this->manager = new GroupManager(['important' => [$file . ':testName']]);

        $this->assertContains('important', $this->manager->groupsForTest($this->makeTestCase($file, 'testName')));
        $this->assertContains('important', $this->manager->groupsForTest($this->makeExample($file, 'testName', 'key', 0)));
        $this->assertContains('important', $this->manager->groupsForTest($this->makeTestCase($file, 'testNameLong')));
    }

    protected function makeTestCase(string $file, string $name = ''): TestCaseWrapper
    {
        $testcase = new TestCaseWrapper(clone $this);

        $metadata = $testcase->getMetadata();
        $metadata->setName($name);
        $metadata->setFilename(codecept_root_dir() . $file);

        return $testcase;
    }

    protected function makeExample(string $file, string $name, int|string $index, int $position): TestCaseWrapper
    {
        $testcase = $this->makeTestCase($file, $name);
        $testcase->getMetadata()->setIndex($index);
        $testcase->getMetadata()->setPosition($position);

        return $testcase;
    }
}
