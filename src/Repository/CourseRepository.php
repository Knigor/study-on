<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function findAllWithBilling(array $billingInfo): array
    {
        // Собираем коды из billingInfo для фильтрации
        $billingCodes = array_column($billingInfo, 'code');


        $billingMap = array_reduce($billingInfo, function ($carry, $item) {
            $carry[$item['code']] = [
                'type' => $item['type'],
                'price' => $item['type'] === 'free' ? 0.00 : (float)$item['price']
            ];
            return $carry;
        }, []);


        $qb = $this->createQueryBuilder('c');
        $qb->select('c', 'l')
            ->leftJoin('c.lessons', 'l')
            ->addOrderBy('l.orderNumber', 'ASC');


        if (!empty($billingCodes)) {
            $qb->andWhere('c.characterCode IN (:codes)')
            ->setParameter('codes', $billingCodes);
        }

        $courses = $qb->getQuery()->getResult();

        // Формируем результат
        return array_map(function (Course $course) use ($billingMap) {
            $courseData = [
                'id' => $course->getId(),
                'title' => $course->getName(),
                'code' => $course->getCharacterCode(),
                'description' => $course->getDescription(),
                'lessons' => $course->getLessons()->toArray(),
                'type' => 'free',
                'price' => 0.00
            ];

            if (isset($billingMap[$course->getCharacterCode()])) {
                $billingData = $billingMap[$course->getCharacterCode()];
                $courseData['type'] = $billingData['type'];
                $courseData['price'] = $billingData['price'];
            }


            return $courseData;
        }, $courses);
    }
}
