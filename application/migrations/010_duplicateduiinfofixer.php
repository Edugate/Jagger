<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_duplicateduiinfofixer extends CI_Migration {

    function up()
    {
        $this->em = $this->doctrine->em;


        $types = array('idp', 'sp', 'aa');
        foreach ($types as $t)
        {
            $col = $this->em->getRepository("models\ExtendMetadata")->findBy(array('etype' => '' . $t . '', 'namespace' => 'mdui'));
            $r = array();
            foreach ($col as $c)
            {
                $pid = $c->getProvider();
                if (!empty($pid))
                {
                    $pp = $c->getElement();
                    if ($pp === 'UIInfo')
                    {
                        $r['' . $pid->getId() . '']['p'][] = $c;
                    }
                    else
                    {
                        $r['' . $pid->getId() . '']['c'][] = $c;
                    }
                }
            }
            foreach ($r as $k => $v)
            {
                if (isset($v['p']))
                {
                    if (count($v['p']) > 1)
                    {
                        $isAssigned = FALSE;
                        foreach ($v['p'] as $parent)
                        {
                            if ($isAssigned)
                            {
                                continue;
                            }
                            else
                            {
                                $newparent = $parent;
                                if (isset($v['c']))
                                {
                                    foreach ($v['c'] as $child)
                                    {
                                        if (!$newparent->getChildren()->contains($child))
                                        {
                                            $newparent->getChildren()->add($child);
                                            $child->setParent($newparent);
                                            $this->em->persist($child);
                                        }
                                    }
                                }
                                $this->em->persist($newparent);
                                $isAssigned = TRUE;
                            }
                        }
                    }
                }
            }
            try
            {
                $this->em->flush();
            }
            catch (Exception $e)
            {
                log_message('error', __METHOD__ . ' ' . $e);
                return FALSE;
            }
        }
        $ps = $this->em->getRepository("models\ExtendMetadata")->findBy(array('namespace' => 'mdui', 'element' => 'UIInfo'));
        foreach ($ps as $p)
        {
            if ($p->getChildren()->isEmpty())
            {
                $this->em->remove($p);
            }
        }
        try
        {
            $this->em->flush();
        }
        catch (Exception $e)
        {
            log_message('error', __METHOD__ . ' ' . $e);
            return FALSE;
        }
        return TRUE;
    }

    function down()
    {
        echo "down";
    }

}
