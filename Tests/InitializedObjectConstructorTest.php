<?php

namespace AC\WebServicesBundle\Tests;

use AC\WebServicesBundle\TestCase;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person;
use AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Group;
use JMS\Serializer\DeserializationContext;
// use AC\WebServicesBundle\Serializer\DeserializationContext;

/**
 **/
class InitializedObjectConstructorTest extends TestCase
{
    public function setUp()
    {
        $this->serializer = $this->getContainer()->get('serializer');
        $this->context = DeserializationContext::create();
        $this->allen = new Person("Allen", 10, 1);
        $this->barry = new Person("Barry", 11, 2);
        $this->clive = new Person("Clive", 12, 3);
        $this->davis = new Person("Davis", 15, 4);
        $this->edgar = new Person("Edgar", 11, 5);

    }

    protected function createAlphas()
    {
        $alphas = new Group();
        $alphas->setOwner($this->allen);
        $alphas->setMembers(array(
               $this->allen,
               $this->barry,
               $this->clive
            )
        );
        return $alphas;
    }

    protected function createBetas()
    {
        $betas = new Group();
        $betas->setOwner($this->clive);
        $betas->setMembers(array(
               $this->barry,
               $this->clive,
               $this->davis
            )
        );
        return $betas;
    }
    protected function createCircularRelationships()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->clive->setBestFriend($this->davis);
        $this->davis->setBestFriend($this->edgar);
        $this->edgar->setBestFriend($this->allen);
        // $this->allen->setOtherFriends(array($this->clive, $this->davis));
        // $this->barry->setOtherFriends(array($this->davis, $this->allen));
        // $this->clive->setOtherFriends(array($this->edgar, $this->allen));
        // $this->davis->setOtherFriends(array($this->allen, $this->barry, $this->clive));
        // $this->davis->setOtherFriends(array($this->barry));
    }

    protected function createNonCircularRelationships()
    {
        $this->allen->setBestFriend($this->barry);
        $this->barry->setBestFriend($this->clive);
        $this->clive->setBestFriend($this->davis);
        $this->davis->setBestFriend($this->edgar);
    }
    protected function deserializeWithNewData($alphas)
    {
        $newData = array(
            'owner' => $this->edgar,
            'members' => array(
                $this->clive,
                $this->davis,
                $this->edgar
            )
        );
        $serializedData = $this->serializer->serialize($newData, "json");

        var_dump($serializedData);

        $this->context->setAttribute('target', $alphas);
        $this->context->setAttribute('updateNestedData', TRUE);
        $modifiedGroup = $this->serializer->deserialize(
            $serializedData,
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Group',
            'json',
            $this->context
        );

        return $modifiedGroup;
    }


    public function testConstruct()
    {

        $existingPerson = new Person('John', 86);
        $this->context->setAttribute('target', $existingPerson);
        $newData = array(
            'age' => 27
        );
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
        $decoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));

        $this->assertEquals(27, $decoded->age);
        $this->assertEquals('John', $decoded->name);
    }












    public function testStuff()
    {
        $p1 = new Person('John', 12, 1);
        $p2 = new Person('Chris', 12, 2);
        $p3 = new Person('Evan', 12, 3);
        $p1->setBestFriend($p2);
        $p2->setBestFriend($p3);
        $p3->setBestFriend($p1);

        $this->context->setAttribute('target', $p1);
        $this->context->setAttribute('updateNestedData', TRUE);
        $modified = $this->serializer->deserialize(json_encode(array(
            'bestFriend' => array(
                'bestFriend' => array(
                    'name' => 'Clive',
                    'age' => 45,
                    'id' => 9001
                )
            )
        )), 'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person', 'json', $this->context);

        $this->assertSame(45, $modified->getBestFriend()->getBestFriend()->age);
        $this->assertSame(3, $modified->getBestFriend()->getBestFriend()->getId());

        $this->assertEquals(spl_object_hash($modified), spl_object_hash($p1));
        $this->assertEquals(spl_object_hash($modified->getBestFriend()), spl_object_hash($p2));
        $this->assertEquals(spl_object_hash($modified->getBestFriend()->getBestFriend()), spl_object_hash($p3));
        $this->assertEquals(spl_object_hash($modified->getBestFriend()->getBestFriend()->getBestFriend()), spl_object_hash($p1));
    }




    public function testComplexStructure()
    {

        $this->context->setAttribute('target', $this->allen);
        $this->allen->setBestFriend($this->edgar);
        $this->context->setAttribute('updateNestedData', TRUE);

        $newData = array(
            'bestFriend' => array(
                'name' => "Alyosha",
                'age' => 10,
                'bestFriend' => array(
                    'name' => "Boris",
                    'age' => 11,
                    'bestFriend' => array(
                        'name' => 'Dimitri',
                        'age' => 12
                    )
                )
            )
        );
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
    }

    public function testMultipleUpdate()
    {

        $this->allen->setOtherFriends(array($this->barry, $this->clive));
        $this->barry->setBestFriend($this->edgar);
        $this->context->setAttribute('target', $this->allen);
        $this->context->setAttribute('updateNestedData', TRUE);
        $newData = array(
            'name' => 'Bazil',
            'otherFriends' => array(
               array(
                    "name" => "Bart",
                    "age"=> 11,
                    "bestFriend" => array(
                        'name' => 'Foobert'
                    )
                ),
               array(
                    "name" => "Chester",
                    "age"=> 11
                )
            )
        );
        $modifiedPerson = $this->serializer->deserialize(
            json_encode($newData),
            'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
            'json',
            $this->context
        );
    }
}


    // public function testNoncircularRelationships()
    // {
    //     $alphas = $this->createAlphas();
    //     $this->createNonCircularRelationships();
    //     $modifiedGroup = $this->deserializeWithNewData($alphas);
    //     $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
    //     $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());
    //     $this->assertEquals($this->barry, $this->allen->getBestFriend());
    // }
    // public function testCircularRelationships()
    // {
    //     $alphas = $this->createAlphas();
    //     $this->createCircularRelationships();
    //     $modifiedGroup = $this->deserializeWithNewData($alphas);
    //     $this->assertEquals($this->edgar, $modifiedGroup->getOwner());
    //     $this->assertEquals(array($this->clive, $this->davis, $this->edgar), $modifiedGroup->getMembers());
    //     $this->assertEquals($this->allen, $this->edgar->getBestFriend());
    //     $this->assertEquals($this->barry, $this->allen->getBestFriend());
    // }
    // public function testArrayUpdate()
    // {

    //     $this->allen->setOtherFriends(array($this->barry, $this->clive));

    //     $newData = array(
    //         'otherFriends' => array(
    //             $this->davis,
    //             $this->edgar
    //         )
    //     );

    //     $serializedData = $this->serializer->serialize($newData, "json");

    //     $this->context->setAttribute('target', $this->allen);
    //     $this->context->setAttribute('updateNestedData', TRUE);
    //     $modifiedPerson = $this->serializer->deserialize(
    //         $serializedData,
    //         'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
    //         'json',
    //         $this->context
    //     );

    //     $this->assertEquals($this->allen->getOtherFriends(),
    //         array($this->davis, $this->edgar));
    // }

    // public function testUpdateLevelOneNesting()
    // {
    //     $this->allen->setBestFriend($this->barry);
    //     $this->context->setAttribute('target', $this->allen);
    //     $this->context->setAttribute('updateNestedData', TRUE);
    //     $newData = array(
    //         'bestFriend' => $this->davis
    //     );
    //     $serializedData = $this->serializer->serialize($newData, "json");

    //     $modifiedPerson = $this->serializer->deserialize(
    //         $serializedData,
    //         'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
    //         'json',
    //         $this->context
    //     );
    //     // var_dump($this->allen);
    //     var_dump($this->barry);
    //     var_dump($this->davis);
    //     $this->assertNotEquals($this->barry, $this->davis);
    //     $this->assertEquals($this->davis, $modifiedPerson->getBestFriend());
    // }

    // public function testUpdateLevelOneComplexNesting()
    // {

    //     $this->allen->setBestFriend($this->barry);
    //     $this->barry->setBestFriend($this->clive);

    //     $this->assertEquals("Barry",
    //         $this->allen->getBestFriend()->getName());
    //     $this->assertEquals("Clive",
    //         $this->allen->getBestFriend()->getBestFriend()->getName());

    //     $this->davis->setBestFriend($this->edgar);
    //     $davisData = $this->serializer->serialize($this->davis,"json");
    //     $newData = array(
    //         'bestFriend' => json_decode($davisData, TRUE)
    //     );
    //     $this->context->setAttribute('target', $this->allen);
    //     $this->context->setAttribute('updateNestedData', TRUE);
    //     $modifiedPerson = $this->serializer->deserialize(
    //         json_encode($newData),
    //         'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
    //         'json',
    //         $this->context
    //     );

    //     $this->assertEquals($this->davis,
    //         $this->allen->getBestFriend());
    //     $this->assertEquals($this->edgar,
    //         $this->allen->getBestFriend()->getBestFriend());
    // }
    // public function testUpdateReverseNesting()
    // {

    //     $this->allen->setBestFriend($this->barry);
    //     $this->barry->setBestFriend($this->clive);
    //     $this->assertEquals("Barry",
    //         $this->allen->getBestFriend()->getName());
    //     $this->assertEquals("Clive",
    //         $this->allen->getBestFriend()->getBestFriend()->getName());
    //     $this->clive->setBestFriend($this->barry);
    //     $cliveData = $this->serializer->serialize($this->clive,"json");
    //     $newData = array(
    //         'bestFriend' => json_decode($cliveData, TRUE)
    //     );
    //     $this->context->setAttribute('target', $this->allen);
    //     $this->context->setAttribute('updateNestedData', TRUE);
    //     $modifiedPerson = $this->serializer->deserialize(
    //         json_encode($newData),
    //         'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
    //         'json',
    //         $this->context
    //     );
    //     $this->assertEquals($this->clive,
    //         $this->allen->getBestFriend());
    //     $this->assertEquals($this->barry,
    //         $this->allen->getBestFriend()->getBestFriend());
    // }
    // public function testUpdateLevelTwoNesting()
    // {

    //     $this->allen->setBestFriend($this->barry);
    //     $this->barry->setBestFriend($this->clive);
    //     $this->assertEquals($this->clive,
    //         $this->allen->getBestFriend()->getBestFriend());
    //     $newData = array(
    //         'bestFriend' => $this->davis
    //     );
    //     $serializedData = $this->serializer->serialize($newData, "json");
    //     $this->context->setAttribute('target', $this->barry);
    //     $this->context->setAttribute('updateNestedData', TRUE);
    //     $modifiedPerson = $this->serializer->deserialize(
    //         $serializedData,
    //         'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\Model\Person',
    //         'json',
    //         $this->context
    //     );
    //     $decoded = json_decode($this->serializer->serialize($modifiedPerson, "json"));
    //     var_dump($this->clive);
    //     var_dump($this->davis);
    //     $this->assertEquals($this->davis,
    //         $this->allen->getBestFriend()->getBestFriend());

    // }
