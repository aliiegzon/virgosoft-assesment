<?php

namespace App\Http\CustomResponse;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CustomResponse extends Response
{
    /**
     * @param int $code
     * @param string $message
     * @param array|JsonResource|ResourceCollection|LengthAwarePaginator|null $object
     * @param array|null $auth
     * @return JsonResponse
     */
    public function resolveResponseStructure(int $code, string $message, array|JsonResource|ResourceCollection|LengthAwarePaginator $object = null, array $auth = null): JsonResponse
    {
        $meta = [
            'code'    => $code,
            'message' => $message,
        ];

        $data = $object;
        if (is_object($object) && isset($object->resource) && $object->resource instanceof LengthAwarePaginator) {
            [$data, $paginationData] = $this->resolveLengthAwarePaginatorData($object->resource);

            $meta['pagination_data'] = $paginationData;
        }

        $responseData = array_filter([
            'meta' => $meta,
            'auth' => $auth,
        ]);

        if(!is_null($data)){
            $responseData['data'] = $data;
        }

        return response()->json($responseData, $code);
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public function resolveLengthAwarePaginatorData(LengthAwarePaginator $paginator): array
    {
        $paginatorData = $paginator->toArray();

        $data = $paginatorData['data'];

        unset($paginatorData['data']);

        return [
            $data,
            $paginatorData,
        ];
    }

    /**
     * @param string $message
     * @param array|JsonResource|ResourceCollection|LengthAwarePaginator|null $object
     * @param array|null $auth
     * @return JsonResponse
     */
    public function success(string $message = 'Success', array|JsonResource|ResourceCollection|LengthAwarePaginator $object = null, array $auth = null): JsonResponse
    {
        return $this->resolveResponseStructure(self::HTTP_OK, $message, $object, $auth);
    }

    /**
     * @param string $message
     * @param array|JsonResource|ResourceCollection|LengthAwarePaginator|null $object
     * @return JsonResponse
     */
    public function created(string $message = 'Success', array|JsonResource|ResourceCollection|LengthAwarePaginator $object = null): JsonResponse
    {
        return $this->resolveResponseStructure(self::HTTP_CREATED, $message, $object);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    public function noContent(string $message = 'No Content'): JsonResponse
    {
        return $this->resolveResponseStructure(self::HTTP_NO_CONTENT, $message);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    public function invalidLoginCredential(string $message = 'Invalid Login Credentials!'): JsonResponse
    {
        return $this->resolveResponseStructure(self::HTTP_UNAUTHORIZED, $message);
    }

    /**
     * @param Throwable|Exception $exception
     * @return JsonResponse
     */
    public function exception(Throwable|Exception $exception): JsonResponse
    {
        return $this->resolveResponseStructure($exception->getCode(), $exception->getMessage());
    }

    /**
     * @param Throwable|Exception $exception
     * @return JsonResponse
     */
    public function validationException(Throwable|Exception $exception): JsonResponse
    {
        return $this->resolveResponseStructure(self::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
    }

    /**
     * @param Throwable|Exception $exception
     * @return JsonResponse
     */
    public function modelNotFoundException(Throwable|Exception $exception): JsonResponse
    {
        return $this->resolveResponseStructure(self::HTTP_NOT_FOUND, $exception->getMessage());
    }

    /**
     * @param Throwable|Exception $exception
     * @param string $message
     * @return JsonResponse
     */
    public function exceptionWithCustomMessage(Throwable|Exception $exception, string $message): JsonResponse
    {
        return $this->resolveResponseStructure($exception->getCode(), $message);
    }

    /**
     * @return JsonResponse
     */
    public function unAuthenticated(): JsonResponse
    {
        return $this->resolveResponseStructure(self::HTTP_UNAUTHORIZED, 'Unauthenticated.');
    }

    /**
     * @return JsonResponse
     */
    public function unAuthorized(): JsonResponse
    {
        return $this->resolveResponseStructure(self::HTTP_FORBIDDEN, 'Unauthorized.');
    }

    /**
     * @param SplFileInfo|string $file
     * @param string|null $fileName
     * @param array $headers
     * @param string $disposition
     * @return BinaryFileResponse
     */
    public function download(SplFileInfo|string $file, string $fileName = null, array $headers = [], string $disposition = 'attachment'): BinaryFileResponse
    {
        $fileName = $fileName ?? basename($file);

        return response()->download($file, $fileName, $headers, $disposition)->deleteFileAfterSend(true);
    }
}
