<?php
/*
 * This file is part of the `aop-io/patchwork-interceptor` package.
 *
 * (c) Nicolas Tallefourtane <dev@nicolab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit http://aop.io
 *
 * @copyright Nicolas Tallefourtane <http://nicolab.net>
 */

namespace PatchworkInterceptor;

use
    Aop\Aop,
    Aop\Exception\InterceptorException,
    Aop\Exception\KindException,
    Aop\Exception\JoinPointException,
    Aop\JoinPoint\Support\JoinPointSupportInterceptorInterface,
    Aop\JoinPoint\Traits,
    Patchwork as Pw,
    Patchwork\Stack
;

/**
 * JoinPoint support for `Patchwork`.
 * @see \Aop\JoinPoint\Support\JoinPointSupportInterceptorInterface
 * @see \SkeletonInterceptor\SkeletonInterceptor
 *
 * @author Nicolas Tallefourtane <dev@nicolab.net>
 */
class JoinPointSupportInterceptor implements JoinPointSupportInterceptorInterface
{
    use
        Traits\ReflectionClassTrait,
        Traits\ReflectionFunctionTrait,
        Traits\ReflectionMethodTrait,
        Traits\ReflectionPropertyTrait
    ;

    /**
     * Current patch.
     */
    protected $patch;

    /**
     * Constructor.
     *
     * @see \Aop\JoinPoint\Support\JoinPointSupportInterceptorInterface::__construct()
     *
     * @param &$patch
     */
    public function __construct(&$patch)
    {
        if (!$patch) {

            throw new InterceptorException(
                '$patch is required.'
            );
        }

        $this->patch = &$patch;
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\JoinPointSupportInterceptorInterface::getPatch()
     */
    public function getPatch()
    {
        return $this->patch;
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\KindSupportInterface::getKind()
     */
    public function getKind()
    {
        return $this->patch['kind'];
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\PointcutSupportInterface::getPointcut()
     */
    public function getPointcut()
    {
        return $this->patch['pointcut'];
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\ArgsGetterSupportInterface::getArgs()
     */
    public function getArgs()
    {
        return $this->patch['args'];
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\ArgsSetterSupportInterface::setArgs()
     */
    public function setArgs(array $args)
    {
        $this->patch['args'] = $args;
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\ClassSupportInterface::getClassName()
     */
    public function getClassName()
    {
        return Stack\topCalledClass();
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\ClassSupportInterface::getObject()
     */
    public function getObject()
    {
        return Stack\top('object');
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\PropertySupportInterface::getPropertyName()
     */
    public function getPropertyName()
    {
        $this->createKindException(Aop::KIND_PROPERTY, __METHOD__);
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\PropertyValueGetterSupportInterface::getPropertyValue()
     */
    public function getPropertyValue()
    {
        $this->createKindException(Aop::KIND_PROPERTY, __METHOD__);
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\PropertyValueSetterSupportInterface::setPropertyValue()
     */
    public function setPropertyValue($value)
    {
        $this->createKindException(Aop::KIND_PROPERTY, __METHOD__);
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\MethodSupportInterface::getMethodName()
     */
    public function getMethodName()
    {
        return Stack\top('function');
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\FunctionSupportInterface::getFunctionName()
     */
    public function getFunctionName()
    {
        return Stack\top('function');
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\ExceptionGetterSupportInterface::getException()
     */
    public function getException()
    {
        return $this->patch['exception'];
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\ReturnValueGetterSupportInterface::getReturnValue()
     */
    public function &getReturnValue()
    {
        return $this->patch['return'];
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\ReturnValueSetterSupportInterface::setReturnValue()
     */
    public function setReturnValue($value)
    {
        $this->patch['return'] = $value;
    }

    /**
     * @inheritdoc
     * @see \Aop\JoinPoint\Support\ProceedSupportInterface::proceed()
     */
    public function proceed()
    {
        $this->createKindException(Aop::KIND_AROUND, __METHOD__);
    }

    /**
     * Create and throws a `\Aop\Exception\KindException`.
     *
     * @param string $method
     * @param int $kind
     */
    protected function createKindException($kind, $method)
    {
        throw new KindException(
            '`'.$method.'` error:
            the interceptor does not support the `'.Aop::getKindName($kind).'` kind.'
        );
    }
}
