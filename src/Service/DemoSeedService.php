<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Attribute;
use App\Entity\Category;
use App\Entity\CurriculumVitae;
use App\Entity\CvLike;
use App\Entity\DiscussionPost;
use App\Entity\Position;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\AccessOperator;
use App\Enum\AttributeType;
use App\Enum\CvStatus;
use App\Repository\AttributeRepository;
use App\Repository\CategoryRepository;
use App\Repository\CvLikeRepository;
use App\Repository\DiscussionPostRepository;
use App\Repository\PositionRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DemoSeedService
{
    public const PASSWORD = 'demo1234';

    public const ADMIN_EMAIL = 'admin@demo.local';
    public const RECRUITER_EMAIL = 'recruiter@demo.local';
    public const ADA_EMAIL = 'ada@demo.local';
    public const BOB_EMAIL = 'bob@demo.local';

    public const JUNIOR_TITLE = 'Junior PHP Developer';
    public const SENIOR_TITLE = 'Senior Backend Engineer';

    private const DISCUSSION = 'We are hiring — publish a CV if you match the stack.';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $users,
        private readonly CategoryRepository $categories,
        private readonly AttributeRepository $attributes,
        private readonly PositionRepository $positions,
        private readonly ProjectRepository $projects,
        private readonly CvLikeRepository $likes,
        private readonly DiscussionPostRepository $posts,
        private readonly PositionService $positionService,
        private readonly ProfileValueService $profileValues,
        private readonly CvService $cvService,
    ) {
    }

    /**
     * @return list<string>
     */
    public function seed(): array
    {
        $personal = $this->upsertCategory('Personal Information');
        $skills = $this->upsertCategory('Skills');
        $experience = $this->upsertCategory('Experience');
        $this->entityManager->flush();

        $this->renamePhotoIfNeeded();

        $firstName = $this->upsertAttribute('First Name', AttributeType::String, true, $personal);
        $lastName = $this->upsertAttribute('Last Name', AttributeType::String, true, $personal);
        $location = $this->upsertAttribute('Location', AttributeType::String, true, $personal);
        $photo = $this->upsertAttribute('Personal Photo', AttributeType::Image, true, $personal);
        $summary = $this->upsertAttribute('Professional summary', AttributeType::Text, false, $personal);
        $years = $this->upsertAttribute('Years of experience', AttributeType::Numeric, false, $experience);
        $english = $this->upsertAttribute('English level', AttributeType::Choice, false, $skills, ['A2', 'B1', 'B2', 'C1', 'C2']);
        $github = $this->upsertAttribute('GitHub', AttributeType::String, false, $skills);
        $this->entityManager->flush();

        $this->upsertUser(self::ADMIN_EMAIL, 'Demo Admin', User::ROLE_ADMIN);
        $recruiter = $this->upsertUser(self::RECRUITER_EMAIL, 'Riley Recruiter', User::ROLE_RECRUITER);
        $ada = $this->upsertUser(self::ADA_EMAIL, 'Ada Candidate', User::ROLE_CANDIDATE);
        $bob = $this->upsertUser(self::BOB_EMAIL, 'Bob Candidate', User::ROLE_CANDIDATE);
        $this->entityManager->flush();

        $junior = $this->upsertPosition(
            self::JUNIOR_TITLE,
            'Public PHP role for candidates who are starting with Symfony.',
            'Demo Labs',
            'Junior',
            true,
            ['php', 'symfony', 'postgresql'],
            2,
            [$summary, $english, $years],
            [(int) $summary->getId() => true, (int) $english->getId() => true, (int) $years->getId() => false],
            [],
        );
        $senior = $this->upsertPosition(
            self::SENIOR_TITLE,
            'Restricted backend role. PHP and PostgreSQL, three or more years.',
            'Demo Labs',
            'Senior',
            false,
            ['php', 'postgresql', 'docker'],
            3,
            [$summary, $years, $github],
            [(int) $summary->getId() => true, (int) $years->getId() => true, (int) $github->getId() => false],
            [[
                'attributeId' => (int) $years->getId(),
                'operator' => AccessOperator::Gte->value,
                'compareValue' => 3,
            ]],
        );
        $this->entityManager->flush();

        $this->fillProfile($ada, [
            [$firstName, 'Ada'],
            [$lastName, 'Candidate'],
            [$location, 'Tashkent'],
            [$photo, 'https://i.pravatar.cc/128?u=ada@demo.local'],
            [$summary, "Backend developer focused on **PHP** and Symfony.\n\nComfortable with PostgreSQL and clear CV templates."],
            [$years, 5],
            [$english, 'C1'],
            [$github, 'https://github.com/demo-ada'],
        ]);
        $this->fillProfile($bob, [
            [$firstName, 'Bob'],
            [$lastName, 'Candidate'],
            [$location, 'Samarkand'],
            [$photo, 'https://i.pravatar.cc/128?u=bob@demo.local'],
            [$summary, 'Junior developer learning PHP and Symfony.'],
            [$years, 1],
            [$english, 'B1'],
        ]);

        $this->upsertProject(
            $ada,
            'Campus CV engine',
            '2024-01-15',
            null,
            "Reusable **attribute library** and generated CVs.\n\nStack: PHP, Symfony, PostgreSQL.",
            ['php', 'symfony', 'postgresql'],
        );
        $this->upsertProject(
            $ada,
            'Tag search prototype',
            '2023-06-01',
            '2023-11-30',
            'Full-text and tag filters over positions.',
            ['php', 'postgresql'],
        );
        $this->upsertProject(
            $bob,
            'Hello PHP',
            '2025-02-01',
            null,
            'First Symfony pages and forms.',
            ['php', 'symfony'],
        );

        $adaJunior = $this->publishCv($ada, $junior);
        $this->publishCv($ada, $senior);
        $this->publishCv($bob, $junior);

        $this->likeIfNeeded($recruiter, $adaJunior);
        $this->discussIfNeeded($recruiter, $junior);

        return [
            self::ADMIN_EMAIL.' (admin)',
            self::RECRUITER_EMAIL.' (recruiter)',
            self::ADA_EMAIL.' (candidate, matches restricted position)',
            self::BOB_EMAIL.' (candidate, public positions only)',
        ];
    }

    private function upsertCategory(string $name): Category
    {
        $category = $this->categories->findOneByName($name);
        if ($category !== null) {
            return $category;
        }

        $category = new Category();
        $category->setName($name);
        $this->entityManager->persist($category);

        return $category;
    }

    /**
     * @param list<string> $options
     */
    private function upsertAttribute(
        string $name,
        AttributeType $type,
        bool $builtIn,
        Category $category,
        array $options = [],
    ): Attribute {
        $attribute = $this->attributes->findOneByName($name);
        if ($attribute === null) {
            $attribute = new Attribute();
            $attribute->setName($name);
            $this->entityManager->persist($attribute);
        }

        $attribute->setType($type->value);
        $attribute->setIsBuiltIn($builtIn);
        $attribute->setCategory($category);
        $attribute->setOptions($options);

        return $attribute;
    }

    private function renamePhotoIfNeeded(): void
    {
        $legacy = $this->attributes->findOneByName('Photo');
        if ($legacy === null || $this->attributes->findOneByName('Personal Photo') !== null) {
            return;
        }

        $legacy->setName('Personal Photo');
    }

    private function upsertUser(string $email, string $name, string $role): User
    {
        $user = $this->users->findOneByEmail($email);
        if ($user === null) {
            $user = new User();
            $user->setEmail($email);
            $this->entityManager->persist($user);
        }

        $user->setName($name);
        $user->setRoles([$role]);
        $user->setIsBlocked(false);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));

        return $user;
    }

    /**
     * @param list<Attribute> $template
     * @param array<int, bool> $requiredById
     * @param list<array{attributeId: int, operator: string, compareValue: mixed}> $rules
     */
    private function upsertPosition(
        string $title,
        string $description,
        string $company,
        string $level,
        bool $public,
        array $tags,
        int $maxProjects,
        array $template,
        array $requiredById,
        array $rules,
    ): Position {
        $position = $this->positions->findOneBy(['title' => $title]);
        if ($position === null) {
            $position = new Position();
            $position->setTitle($title);
            $this->entityManager->persist($position);
        }

        $position->setShortDescription($description);
        $position->setCompany($company);
        $position->setLevel($level);
        $position->setIsPublic($public);
        $position->setProjectTags($tags);
        $position->setMaxProjects($maxProjects);
        $this->entityManager->flush();

        $ids = [];
        $indexed = [];
        foreach ($template as $attribute) {
            $id = $attribute->getId();
            if ($id === null) {
                continue;
            }
            $ids[] = $id;
            $indexed[$id] = $attribute;
        }

        $this->positionService->applyTemplate($position, $ids, $requiredById, $rules, $indexed);
        $this->entityManager->flush();

        return $position;
    }

    /**
     * @param list<array{0: Attribute, 1: mixed}> $pairs
     */
    private function fillProfile(User $user, array $pairs): void
    {
        $items = [];
        $indexed = [];
        foreach ($pairs as [$attribute, $value]) {
            if ($attribute->getId() === null) {
                continue;
            }
            $id = $attribute->getId();
            $indexed[$id] = $attribute;
            $items[] = [
                'attributeId' => $id,
                'value' => $value,
            ];
        }

        $this->profileValues->upsertMany($user, $items, $indexed);
    }

    /**
     * @param list<string> $tags
     */
    private function upsertProject(
        User $owner,
        string $name,
        string $start,
        ?string $end,
        string $description,
        array $tags,
    ): void {
        $project = $this->projects->findOneBy(['owner' => $owner, 'name' => $name]);
        if ($project === null) {
            $project = new Project();
            $project->setOwner($owner);
            $project->setName($name);
            $this->entityManager->persist($project);
        }

        $project->setStartDate(new \DateTimeImmutable($start));
        $project->setEndDate($end === null ? null : new \DateTimeImmutable($end));
        $project->setDescription($description);
        $project->setTechnologyTags($tags);
        $project->touch();
        $this->entityManager->flush();
    }

    private function publishCv(User $user, Position $position): CurriculumVitae
    {
        $withTemplate = $this->positions->findOneWithTemplate((int) $position->getId()) ?? $position;
        $cv = $this->cvService->getOrCreate($user, $withTemplate);
        if ($cv->getStatus() !== CvStatus::Published->value) {
            $this->cvService->publish($cv);
        }

        return $cv;
    }

    private function likeIfNeeded(User $recruiter, CurriculumVitae $cv): void
    {
        if ($this->likes->findOneByRecruiterAndCv($recruiter, $cv) !== null) {
            return;
        }

        $like = new CvLike();
        $like->setRecruiter($recruiter);
        $cv->addLike($like);
        $this->entityManager->persist($like);
        $this->entityManager->flush();
    }

    private function discussIfNeeded(User $author, Position $position): void
    {
        $existing = $this->posts->findOneBy([
            'position' => $position,
            'author' => $author,
            'content' => self::DISCUSSION,
        ]);
        if ($existing !== null) {
            return;
        }

        $post = new DiscussionPost();
        $post->setPosition($position);
        $post->setAuthor($author);
        $post->setContent(self::DISCUSSION);
        $this->entityManager->persist($post);
        $this->entityManager->flush();
    }
}
