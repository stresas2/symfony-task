<?php

namespace App\Controller;

use App\DTO\CreateShiftRequest;
use App\Repository\ShiftRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ShiftController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly LockFactory $lockFactory,
        private readonly ShiftRepository $shiftRepository,
    ) {
    }

    #[Route('/shifts', name: 'shift_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $shiftRequest = $this->serializer->deserialize(
                $request->getContent(),
                CreateShiftRequest::class,
                'json',
                [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true],
            );
        } catch (PartialDenormalizationException $e) {
            $errors = [];
            foreach ($e->getErrors() as $error) {
                $errors[] = sprintf('%s has an invalid value.', $error->getPath());
            }

            return $this->errorResponse('Unexpected value error', $errors);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error deserialize data', [$e->getMessage()]);
        }

        $lock = $this->lockFactory->createLock('shift-create-' . $shiftRequest->getEmployeeId());
        $lock->acquire(true);

        try {
            $violations = $this->validator->validate($shiftRequest);
            if ($violations->count() > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = (string)$violation->getMessage();
                }

                return $this->errorResponse('Validation error', $errors);
            }

            $shift = $this->shiftRepository->saveFromRequest($shiftRequest);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error create shift', [$e->getMessage()]);
        } finally {
            $lock->release();
        }

        return $this->json($shift, Response::HTTP_CREATED);
    }
}
