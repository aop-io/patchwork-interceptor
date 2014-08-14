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
    Aop\Exception\KindException,
    Aop\Exception\PointcutException,
    Aop\Weaver\Interceptor,
    Aop\Advice\LazyAdvice,
    Aop\Advice\AdviceInterface,
    Aop\JoinPoint\JoinPoint,
    Aop\Pointcut\PointcutInterface,
    Aop\Pointcut\Pointcut,
    Patchwork as Pw,
    Patchwork\Stack
;

/**
 * AOP with the Patchwork package (stream wrapper), PHP code interceptor for PHP-AOP.
 * @see \Aop\Weaver\Interceptor
 *
 * @author Nicolas Tallefourtane <dev@nicolab.net>
 */
class PatchworkInterceptor extends Interceptor
{

    /**
     * The context
     *
     * @var array
     *   [<index> => [
     *     'binder'      => <Closure>, references of callbacks to execute when weaving
     *     'called'      => <bool>, status of advice execution (called or not called)
     *     'enabled'     => <bool>, is enabled or disabled
     *     'selector'    => <string>, current selector
     *     'pointcut'    => <PointcutInterface>, instance of PointcutInterface
     *     'advice'      => <AdviceInterface>, instance of AdviceInterface
     *     'kind'        => <int>, current kind
     *     'args'        => <array|null>, current arguments
     *     'return'      => <mixed>, current return value
     *     'exception'   => <Exception|null>, current exception (null otherwise)
     *     'handle'      => <\Patchwork\PatchHandle>, Patchwork handle
     *     'replacement' => <callable>, function or method replacement passed to Patchwork
     *   ]]
     */
    private static $context = [];

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::isEnabled()
     */
    public function isEnabled($index = null, $selector = null)
    {
        // if by index (the first index is 1)
        if($index) {
            return self::$context[ $index ]['enabled'];
        }

        $enabled = null;

        foreach (self::$context as $index => $opt) {

            if($selector == $opt['selector']) {
                $enabled = ((true === $opt['enabled']) ? true : false);
            }
        }

        return $enabled;
    }

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::enable()
     */
    public function enable($index = null, $selector = null)
    {
        $enable = function ($index) {
            self::$context[$index]['enabled'] = true;

            self::$context[$index]['handle'] = $this->pwReplace(
                self::$context[$index]['pointcut']->getPointcut(),
                self::$context[$index]['replacement']
            );

            $this->doBindAdvice($index);
        };

        // if by index (the first index is 1)
        if ($index) {
            $enable($index);

            return $this;
        }

        foreach (self::$context as $index => $opt) {

            if ($selector == $opt['selector']) {
                $enable($index);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::disable()
     */
    public function disable($index = null, $selector = null)
    {
        $disable = function($index) {

            self::$context[$index]['enabled'] = false;

            Pw\undo(self::$context[$index]['handle']);

            $this->doBindOriginalCode($index);
        };

        if($index) {
            $disable($index);

            return $this;
        }


        foreach (self::$context as $index => $opt) {

            if ($selector == $opt['selector']) {
                $disable($index);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::getPointcut()
     */
    public function getPointcut($index)
    {
        return self::$context[$index]['pointcut'];
    }

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::getIndexOfSelector()
     */
    public function getIndexOfSelector($selector, $status = WeaverInterface::ENABLE)
    {
        $idx = [];

        if(null !== $status) {
            $status = ($status === WeaverInterface::ENABLE) ? true : false;
        }

        foreach (self::$context as $index => $opt) {

            if ($selector == $opt['selector']
                AND (null === $status OR $status === $opt['enabled']))
            {
                $idx[] = $index;
            }
        }

        return $idx;
    }

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::addBefore()
     */
    public function addBefore(Pointcutinterface $pointcut, AdviceInterface $advice,
                              array $options = [])
    {
        $callback = $this->createBinder(
            Aop::KIND_BEFORE, $pointcut, $advice, $options
        );

        $index = $this->lastIndex;

        $invoke = function () use ($index) {
            $this->pwInvoke($index);
        };

        $context = &self::$context[$index];

        self::$context[$index]['replacement'] = function() use ($callback, $invoke, &$context) {

            $callback();
            $invoke();
            return $context['return'];
        };

        self::$context[$index]['handle'] = $this->pwReplace(
            $pointcut->getSelector(), self::$context[$index]['replacement']
        );

        return $this->lastIndex;
    }

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::addAround()
     */
    public function addAround(Pointcutinterface $pointcut, AdviceInterface $advice,
                              array $options = [])
    {
        throw new KindException(
            '`'.__METHOD__.'` error:
            the interceptor does not support the `'.Aop::getKindName(Aop::KIND_AROUND).'` kind.'
        );
    }

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::addAfter()
     */
    public function addAfter(Pointcutinterface $pointcut, AdviceInterface $advice,
                             array $options = [])
    {
        $callback = $this->createBinder(
            Aop::KIND_AFTER, $pointcut, $advice, $options
        );

        $index = $this->lastIndex;

        $invoke = function () use ($index) {
            $this->pwInvoke($index);
        };

        self::$context[$index]['replacement'] = function() use ($callback, $invoke) {

            $invoke();
            $callback();
        };

        self::$context[$index]['handle'] = $this->pwReplace(
            $pointcut->getSelector(), self::$context[$index]['replacement']
        );

        return $this->lastIndex;
    }

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::addAfterThrow()
     */
    public function addAfterThrow(PointcutInterface $pointcut, AdviceInterface $advice,
                                  array $options = [])
    {
        $callback = $this->createBinder(
            Aop::KIND_AFTER_RETURN, $pointcut, $advice, $options
        );

        $index = $this->lastIndex;

        $invoke = function () use ($index) {
            $this->pwInvoke($index);
        };

        $context = &self::$context[$index];

        self::$context[$index]['replacement'] = function() use ($callback, $invoke, &$context) {

            try{
                $invoke();
            }catch(\Exception $e){
                $context['exception'] = $e;
                $callback();
            }
        };

        self::$context[$index]['handle'] = $this->pwReplace(
            $pointcut->getSelector(), self::$context[$index]['replacement']
        );

        return $this->lastIndex;
    }

    /**
     * @inheritdoc
     * @see \Aop\Weaver\WeaverInterface::addAfterReturn()
     */
    public function addAfterReturn(PointcutInterface $pointcut, AdviceInterface $advice,
                                   array $options = [])
    {
        $callback = $this->createBinder(
            Aop::KIND_AFTER_RETURN, $pointcut, $advice, $options
        );

        $index = $this->lastIndex;

        $invoke = function () use ($index) {
            $this->pwInvoke($index);
        };

        self::$context[$index]['replacement'] = function() use ($callback, $invoke) {

            $invoke();
            $callback();
        };

        self::$context[$index]['handle'] = $this->pwReplace(
            $pointcut->getSelector(), self::$context[$index]['replacement']
        );

        return $this->lastIndex;
    }

    /**
     * Resolve the `JoinPoint`
     *
     * @see PeclAopInterceptor::resolveKind()
     *
     * @param  int                     $index  Index of `PatchworkInterceptor::$context`.
     *
     * @return \Aop\JoinPoint\JoinPoint The join point.
     *  The kind of `JoinPoint` depends on the context of the aspect.
     *
     * @throws \Aop\Exception\KindException If the kind of advice is invalid.
     */
    protected function resolveJoinPoint($index)
    {
        // In this interceptor, the pointcut is identical to the selector
        $pointcut = self::$context[$index]['pointcut']->getSelector();

        // if is a method
        if(false === strpos($pointcut, '::')) {
            self::$context[$index]['kind'] += Aop::KIND_FUNCTION;
        }else{
            self::$context[$index]['kind'] += Aop::KIND_METHOD;
        }

        // create an instance of JointPoint (kind resolved)
        return $this->createJoinPoint(

            // kind
            self::$context[$index]['kind'],

            // Pointcut instance with the pointcut string
            self::$context[$index]['pointcut']->setPointcut($pointcut),

            // support of JoinPoint provided by the interceptor
            new JoinPointSupportInterceptor(self::$context[$index])
        );
    }

    /**
     * Create a callback which bind the advice in the weaver.
     *
     * @param int                               $kind       The kind.
     *
     * @param \Aop\Pointcut\PointcutInterface   $pointcut   The pointcut instance containing
     *                                                      the selector.
     *
     * @param \Aop\Advice\AdviceInterface $advice           The callback to invoke
     *                                                      if pointcut is triggered.
     *
     * @param  array                            $options    An array of options for the advice.
     *
     * @return \Closure                          The callback (advice) for the weaver.
     *
     * @throws \Aop\Exception\PointcutException  If the pointcut does not contain the selector.
     */
    protected function createBinder($kind, PointcutInterface $pointcut, AdviceInterface $advice,
                                    array $options = [])
    {
        // assign the index of this
        $this->lastIndex++;
        $index = $this->lastIndex;

        // if options for the advice
        if(!empty($options['advice'])) {
            $advice->addOptions($options['advice']);
        }

        if(!$pointcut->getSelector()) {
            throw new PointcutException('The instance of the pointcut must contain the selector.');
        }

        // add the advice in the queue
        self::$context[$index] = [
            'advice'       => $advice,
            'pointcut'     => $pointcut,
            'binder'       => null,
            'called'       => false,
            'enabled'      => true,
            'args'         => null,
            'exception'    => null,
            'kind'         => $kind
        ];

        // create the reference of the context to add to Patchwork
        $context = &self::$context;

        // add the advice in the binder (container of callback) and bind the advice
        $this->doBindAdvice($index);

        return function () use ($index, &$context) {

            // change the status
            $context[$index]['called'] = true;

            // arguments passed to the intercepted function
            $context[$index]['args'] = Stack\top('args');


            // Resolve to JoinPoint
            // and execute the registered callback
            // for this pointcut (the advice or the original code)
            return $context[$index]['binder']($this->resolveJoinPoint($index));
        };
    }

    /**
     * Make that the binder (container of callback) bind the original code.
     *
     * @param  int           $index  Index of `PatchworkInterceptor::$context`.
     * @return PeclAopInterceptor The current instance.
     */
    private function doBindOriginalCode($index)
    {
        // [index][selector]
        self::$context[$index]['binder'] = function (JoinPoint $jp) {

            $kind = $jp->getKind();

            if(in_array($kind, [
                Aop::KIND_AFTER_FUNCTION
                OR Aop::KIND_AFTER_FUNCTION_RETURN
                OR Aop::KIND_AFTER_FUNCTION_THROW
                OR Aop::KIND_AFTER_METHOD
                OR Aop::KIND_AFTER_METHOD_RETURN
                OR Aop::KIND_AFTER_METHOD_THROW
            ]))
            {
                $jp->getReturnValue();
            }

            if(in_array($kind, [
                Aop::KIND_AROUND_FUNCTION,
                Aop::KIND_AROUND_METHOD,
            ]))
            {
                $jp->proceed();
            }
        };

        return $this;
    }

    /**
     * Make that the binder (container of callback) bind the advice.
     *
     * @param  int           $index  Index of `PatchworkInterceptor::$context`.
     * @return PeclAopInterceptor The current instance.
     */
    private function doBindAdvice($index)
    {
        $context = &self::$context;

        self::$context[$index]['binder'] = function (JoinPoint $jp) use ($index, &$context) {
            return $context[$index]['advice']($jp);
        };

        return $this;
    }


    /*----------------------------------------------------------------------------*\
      Patchwork: abstraction layer
    \*----------------------------------------------------------------------------*/

    /**
     * Replace a callable
     *
     * @param  callable $original
     * @param  callable $redefinition
     * @return \Patchwork\PatchHandle
     */
    protected function pwReplace($original, $redefinition){

        if(function_exists($original)){
            return Pw\replace($original, $redefinition);
        }else{
            return Pw\replaceLater($original, $redefinition);
        }
    }

    /**
     * Invoke the original callable intercepted.
     *
     * @param int $index Index of `PatchworkInterceptor::$context`.
     */
    protected function pwInvoke($index)
    {
        $current = Stack\top();

        if($current['object']) {
            $callOrigin = array($current['object'], $current['function']);
        }else{
            $callOrigin = $current['function'];
        }

        $this->disable($index);

        self::$context[$index]['return'] = call_user_func_array(
            $callOrigin,
            self::$context[$index]['args']
        );

        $this->enable($index);
    }
}
