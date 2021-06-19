<?php

declare(strict_types=1);

use Codeception\Exception\ModuleException;
use Codeception\Module\Doctrine2;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\UuidInterface;

final class PurgeTest extends Unit
{
    /**
     * @var EntityManager
     */
    private $em;

    private $container;

    protected static function _setUpBeforeClass()
    {
        if (false === Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }
    }

    /**
     * @throws ORMException
     */
    protected function _setUp()
    {
        if (!class_exists(EntityManager::class)) {
            $this->markTestSkipped('doctrine/orm is not installed');
        }

        if (!class_exists(Doctrine\Common\Annotations\Annotation::class)) {
            $this->markTestSkipped('doctrine/annotations is not installed');
        }

        $dir = __DIR__ . "/../../../data/doctrine2_entities";

        require_once $dir . "/CompositePrimaryKeyEntity.php";
        require_once $dir . "/PlainEntity.php";
        require_once $dir . "/EntityWithConstructorParameters.php";
        require_once $dir . "/JoinedEntityBase.php";
        require_once $dir . "/JoinedEntity.php";
        require_once $dir . "/EntityWithEmbeddable.php";
        require_once $dir . "/NonTypicalPrimaryKeyEntity.php";
        require_once $dir . "/QuirkyFieldName/Association.php";
        require_once $dir . "/QuirkyFieldName/AssociationHost.php";
        require_once $dir . "/QuirkyFieldName/Embeddable.php";
        require_once $dir . "/QuirkyFieldName/EmbeddableHost.php";
        require_once $dir . "/MultilevelRelations/A.php";
        require_once $dir . "/MultilevelRelations/B.php";
        require_once $dir . "/MultilevelRelations/C.php";
        require_once $dir . "/CircularRelations/A.php";
        require_once $dir . "/CircularRelations/B.php";
        require_once $dir . "/CircularRelations/C.php";
        require_once $dir . '/EntityWithUuid.php';


        $this->em = EntityManager::create(
            ['url' => 'sqlite:///:memory:'],
            Setup::createAnnotationMetadataConfiguration([$dir], true, null, null, false)
        );

        (new SchemaTool($this->em))->createSchema([
            $this->em->getClassMetadata(CompositePrimaryKeyEntity::class),
            $this->em->getClassMetadata(PlainEntity::class),
            $this->em->getClassMetadata(EntityWithConstructorParameters::class),
            $this->em->getClassMetadata(JoinedEntityBase::class),
            $this->em->getClassMetadata(JoinedEntity::class),
            $this->em->getClassMetadata(EntityWithEmbeddable::class),
            $this->em->getClassMetadata(NonTypicalPrimaryKeyEntity::class),
            $this->em->getClassMetadata(\QuirkyFieldName\Association::class),
            $this->em->getClassMetadata(\QuirkyFieldName\AssociationHost::class),
            $this->em->getClassMetadata(\QuirkyFieldName\Embeddable::class),
            $this->em->getClassMetadata(\QuirkyFieldName\EmbeddableHost::class),
            $this->em->getClassMetadata(\MultilevelRelations\A::class),
            $this->em->getClassMetadata(\MultilevelRelations\B::class),
            $this->em->getClassMetadata(\MultilevelRelations\C::class),
            $this->em->getClassMetadata(\CircularRelations\A::class),
            $this->em->getClassMetadata(\CircularRelations\B::class),
            $this->em->getClassMetadata(\CircularRelations\C::class),
            $this->em->getClassMetadata(\EntityWithUuid::class),
        ]);

        $this->container = \Codeception\Util\Stub::make('Codeception\Lib\ModuleContainer');
    }

    private function _preloadFixtures()
    {
        if (!class_exists(\Doctrine\Common\DataFixtures\Loader::class)
            || !class_exists(\Doctrine\Common\DataFixtures\Purger\ORMPurger::class)
            || !class_exists(\Doctrine\Common\DataFixtures\Executor\ORMExecutor::class)) {
            $this->markTestSkipped('doctrine/data-fixtures is not installed');
        }

        $dir = __DIR__ . "/../../../data/doctrine2_fixtures";

        require_once $dir . "/TestFixture1.php";
        require_once $dir . "/TestFixture3.php";
    }

    public function testPurgeWithoutTables(): void
    {
        $this->_preloadFixtures();

        $module = new Doctrine2($this->container, [
            'connection_callback' => function () {
                return $this->em;
            },
        ]);

        $module->_initialize();
        $module->_beforeSuite();

        $module->loadFixtures([TestFixture3::class], false);
        $module->loadFixtures([TestFixture1::class], false);

        $module->dontSeeInRepository(EntityWithConstructorParameters::class, ['name' => 'from TestFixture3']);
    }

    public function testPurgeWithTable(): void
    {
        $this->_preloadFixtures();

        $module = new Doctrine2($this->container, [
            'connection_callback' => function () {
                return $this->em;
            },
            'excluded_tables' => [EntityWithConstructorParameters::class],
        ]);

        $module->_initialize();
        $module->_beforeSuite();

        $module->loadFixtures([TestFixture3::class], false);
        $module->loadFixtures([TestFixture1::class], false);

        $module->seeInRepository(EntityWithConstructorParameters::class, ['name' => 'from TestFixture3']);
    }
}
